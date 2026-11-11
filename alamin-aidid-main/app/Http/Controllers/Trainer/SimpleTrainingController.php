<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\SimpleTraining\TrainerSimpleTrainingReportService;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SimpleTrainingController extends Controller
{
    public function simpleOverview(
        string $courseId,
        TrainerSimpleTrainingReportService $reportService
    ) {
        if ($response = $this->workspaceGuard($courseId)) {
            return $response;
        }

        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) $course->course_id;
        $report = $reportService->overview($courseId, (string) session('user_id'));

        return view('trainer.simple.overview', array_merge(['course' => $course], $report));
    }

    public function simpleParticipants(Request $request, string $courseId, TrainerSimpleTrainingReportService $reportService)
    {
        if ($response = $this->workspaceGuard($courseId)) {
            return $response;
        }

        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) $course->course_id;
        $report = $reportService->participants(
            $courseId,
            $reportService->normalizeParticipantFilters($request)
        );

        if ($request->ajax()) {
            return view('trainer.simple.participants.partials.results', array_merge(['course' => $course], $report));
        }

        return view('trainer.simple.participants', array_merge(['course' => $course], $report));
    }

    public function simpleMaterials(Request $request, string $courseId, TrainerSimpleTrainingReportService $reportService)
    {
        if ($response = $this->workspaceGuard($courseId)) {
            return $response;
        }

        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) $course->course_id;
        $report = $reportService->materials(
            $courseId,
            $reportService->normalizeMaterialFilters($request)
        );

        if ($request->ajax()) {
            return view('trainer.simple.materials.partials.results', array_merge(['course' => $course], $report));
        }

        return view('trainer.simple.materials', array_merge(['course' => $course], $report));
    }

    public function storeSimpleMaterial(Request $request, string $courseId)
    {
        if ($response = $this->workspaceGuard($courseId)) {
            return $response;
        }

        $request->validate([
            'material_file' => [
                'required', 'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,webp,gif,mp4,mov,mp3,wav',
                'max:20480',
            ],
        ], [
            'material_file.required' => 'Please choose a file to upload.',
            'material_file.mimes' => 'That file type is not supported.',
            'material_file.max' => 'The file must not be larger than 20 MB.',
        ]);

        $trainerId = SharedTrainingSchema::currentTrainerId();
        $file = $request->file('material_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $typeMap = [
            'pdf' => 'PDF',
            'doc' => 'Document', 'docx' => 'Document',
            'ppt' => 'Slides', 'pptx' => 'Slides',
            'xls' => 'Spreadsheet', 'xlsx' => 'Spreadsheet',
            'jpg' => 'Image', 'jpeg' => 'Image', 'png' => 'Image', 'webp' => 'Image', 'gif' => 'Image',
            'mp4' => 'Video', 'mov' => 'Video',
            'mp3' => 'Audio', 'wav' => 'Audio',
        ];
        $path = $file->store('course-materials/'.$courseId, 'public');

        $materialId = DB::table('learning_material')->insertGetId([
            'course_id' => $courseId,
            'trainer_id' => $trainerId,
            'session_id' => null,
            'original_file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'material_type' => $typeMap[$extension] ?? 'File',
            'metadata_cleared' => 0,
            'extracted_metadata' => null,
            'ai_generated_title' => null,
            'ai_synopsis' => null,
            'uploaded_at' => now(),
        ]);

        TrainerActivityLogger::log(
            $trainerId,
            'material.upload',
            $file->getClientOriginalName(),
            $courseId,
            'material',
            $materialId
        );

        return redirect()
            ->route('trainer.simple.materials', $courseId)
            ->with('success', 'Material uploaded successfully.');
    }

    public function viewSimpleMaterial(int $materialId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $material = DB::table('learning_material')->where('material_id', $materialId)->first();
        if (! $material) {
            abort(404, 'Material not found.');
        }
        if (! $this->trainerCanAccessCourse((string) $material->course_id)) {
            abort(403, 'You are not allowed to view this material.');
        }
        if (! $material->file_path || ! Storage::disk('public')->exists($material->file_path)) {
            abort(404, 'File missing on disk.');
        }

        $filename = $material->original_file_name ?: basename($material->file_path);

        return response()->file(Storage::disk('public')->path($material->file_path), [
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $filename).'"',
        ]);
    }

    public function deleteSimpleMaterial(Request $request, int $materialId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $material = DB::table('learning_material')->where('material_id', $materialId)->first();
        if (! $material) {
            return redirect()->route('trainer.courses')->with('error', 'Material not found.');
        }
        if (! $this->trainerCanAccessCourse((string) $material->course_id)) {
            return redirect()->route('trainer.courses')->with('error', 'You are not allowed to do that.');
        }

        if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }
        DB::table('learning_material')->where('material_id', $materialId)->delete();

        TrainerActivityLogger::log(
            SharedTrainingSchema::currentTrainerId(),
            'material.delete',
            $material->original_file_name ?: 'Learning material',
            (string) $material->course_id,
            'material',
            (int) $materialId
        );

        return redirect()
            ->route('trainer.simple.materials', array_merge(
                ['course_id' => $material->course_id],
                $this->materialReturnState($request)
            ))
            ->with('success', 'Material has been deleted.');
    }

    public function simpleAttendance(Request $request, string $courseId, TrainerSimpleTrainingReportService $reportService)
    {
        if ($response = $this->workspaceGuard($courseId)) {
            return $response;
        }

        $course = SharedTrainingSchema::course($courseId);
        $courseId = (string) $course->course_id;
        $report = $reportService->attendance(
            $courseId,
            (string) session('user_id'),
            $reportService->normalizeAttendanceFilters($request)
        );

        if ($request->ajax()) {
            return view('trainer.simple.attendance.partials.results', array_merge(['course' => $course], $report));
        }

        return view('trainer.simple.attendance', array_merge(['course' => $course], $report));
    }

    private function workspaceGuard(string $courseId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }
        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')->with('error', 'You are not assigned to this training.');
        }

        $course = SharedTrainingSchema::course($courseId);
        if (! $course) {
            return redirect()->route('trainer.courses')->with('error', 'Training not found.');
        }
        if (strtolower($course->training_mode ?? 'standard') === 'lms') {
            return redirect()->route('trainer.courses.show', $courseId);
        }

        return null;
    }

    private function materialReturnState(Request $request): array
    {
        $sort = $request->string('sort')->toString();
        $rows = $request->integer('rows');

        return array_filter([
            'search' => mb_substr(trim($request->string('search')->toString()), 0, 255) ?: null,
            'sort' => in_array($sort, ['newest', 'oldest', 'name-asc', 'name-desc'], true) && $sort !== 'newest' ? $sort : null,
            'type' => mb_substr(trim($request->string('type')->toString()), 0, 40) ?: null,
            'rows' => in_array($rows, [10, 25, 50], true) && $rows !== 10 ? $rows : null,
            'page' => max(1, $request->integer('page', 1)) > 1 ? max(1, $request->integer('page', 1)) : null,
        ], fn ($value) => $value !== null);
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        return SharedTrainingSchema::trainerAssignedToCourse((string) session('user_id'), $courseId);
    }
}
