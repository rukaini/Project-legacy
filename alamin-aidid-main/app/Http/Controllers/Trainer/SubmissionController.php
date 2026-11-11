<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Lms\TrainerLmsSubmissionFileResolver;
use App\Services\Lms\TrainerLmsSubmissionReportService;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly TrainerLmsSubmissionFileResolver $submissionFiles
    ) {}

    public function submissions(
        Request $request,
        string $courseId,
        TrainerLmsSubmissionReportService $reports
    ) {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()
                ->route('trainer.courses')
                ->with('error', 'You are not assigned to this training.');
        }

        $course = SharedTrainingSchema::course($courseId);

        if (! $course) {
            return redirect()
                ->route('trainer.courses')
                ->with('error', 'Training not found.');
        }

        $courseId = (string) $course->course_id;
        $report = $reports->submissions($courseId, $request);
        $data = array_merge(['course' => $course], $report);

        if ($request->ajax() || $request->expectsJson()) {
            $fragment = $request->query('fragment') === 'results'
                ? 'trainer.training.submission.partials.roster-results'
                : 'trainer.training.submission.partials.workspace';

            return response(view($fragment, $data)->render())
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return view('trainer.training.submission', $data);
    }

    public function downloadSubmissionFile(string $courseId, int $submissionId)
    {
        if (session('user_role') !== 'trainer') {
            abort(401, 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            abort(403, 'You are not assigned to this training.');
        }

        $submission = $this->submissionFile($courseId, $submissionId);

        if (! $submission || ! $submission->submitted_file_path) {
            abort(404, 'Submitted file not found.');
        }

        $storedPath = (string) $submission->submitted_file_path;
        $sourcePath = $this->submissionFiles->resolve($storedPath);
        if ($sourcePath === null || ! $this->submissionFiles->exists($storedPath)) {
            abort(404, 'This submission file could not be found in storage.');
        }

        $displayName = $this->submissionFiles->safeFileName($submission->original_file_name, $storedPath);

        return response()->download($sourcePath, $displayName, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function previewSubmissionFile(string $courseId, int $submissionId)
    {
        if (session('user_role') !== 'trainer') {
            abort(401, 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse((string) $courseId)) {
            abort(403, 'You are not assigned to this training.');
        }

        $submission = $this->submissionFile($courseId, $submissionId);

        if (! $submission || ! $submission->submitted_file_path) {
            abort(404, 'Submitted file not found.');
        }

        $storedPath = (string) $submission->submitted_file_path;
        $sourcePath = $this->submissionFiles->resolve($storedPath);
        if ($sourcePath === null || ! $this->submissionFiles->exists($storedPath)) {
            abort(404, 'This submission file could not be found in storage.');
        }

        $displayName = $this->submissionFiles->safeFileName($submission->original_file_name, $storedPath);

        return response()->file($sourcePath, [
            'Content-Type' => $this->submissionFiles->mimeType($sourcePath, $displayName),
            'Content-Disposition' => 'inline; filename="'.$displayName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function markSubmissionReviewed(
        Request $request,
        string $courseId,
        int $submissionId,
        TrainerLmsSubmissionReportService $reports
    ) {
        $wantsJson = $request->expectsJson() || $request->ajax()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        $fail = function (string $message, int $status = 422) use ($wantsJson) {
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $message], $status);
            }

            return back()->with('error', $message);
        };

        if (session('user_role') !== 'trainer') {
            return $fail('Please login as trainer first.', 401);
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return $fail('You are not assigned to this training.', 403);
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();

        $submissionQuery = DB::table('assessment_submission')
            ->join('assessment', 'assessment_submission.assessment_id', '=', 'assessment.assessment_id')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id');
        SharedTrainingSchema::joinNewTeacherEnrollmentIdentities($submissionQuery);
        $submission = $submissionQuery
            ->where('assessment_submission.submission_id', $submissionId)
            ->where('assessment.course_id', $courseId)
            ->whereRaw('LOWER(assessment.type) = ?', ['submission'])
            ->where('enrollment.course_id', $courseId)
            ->select(
                'assessment_submission.submission_id',
                'assessment_submission.assessment_id',
                'assessment_submission.status',
                'assessment.title as assessment_title',
                DB::raw(SharedTrainingSchema::newTeacherNameExpression().' AS participant_name')
            )
            ->first();

        if (! $submission) {
            return $fail('Submission not found.', 404);
        }

        $requestedStatus = (string) $request->input('review_status', 'Reviewed');
        if (! in_array($requestedStatus, ['Reviewed', 'Submitted'], true)) {
            return $fail('Invalid review status.');
        }

        $isReviewing = ($requestedStatus === 'Reviewed');
        $newStatus = $isReviewing ? 'Reviewed' : 'Submitted';
        $statusChanged = strcasecmp(trim((string) ($submission->status ?? '')), $newStatus) !== 0;

        if ($statusChanged) {
            DB::table('assessment_submission')
                ->where('submission_id', $submissionId)
                ->update([
                    'status' => $newStatus,
                    'graded_by_trainer_id' => $isReviewing ? $trainerId : null,
                    'graded_at' => $isReviewing ? now() : null,
                    'updated_at' => now(),
                ]);
        }

        $detail = ($isReviewing ? 'Marked reviewed: ' : 'Undid review: ')
            .$submission->participant_name
            .' for '.$submission->assessment_title;

        if ($statusChanged) {
            TrainerActivityLogger::log(
                $trainerId,
                'submission.review',
                $detail,
                $courseId,
                'submission',
                $submissionId
            );
        }

        if ($wantsJson) {
            $counts = $reports->taskCounts($courseId, (int) $submission->assessment_id);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $isReviewing ? 'Submission marked as reviewed.' : 'Review undone.',
                'counts' => $counts,
                'pending_count' => $reports->pendingReviewCount($courseId),
            ]);
        }

        return back()->with('success', $isReviewing
            ? 'Submission marked as reviewed.'
            : 'Review undone.');
    }

    public function downloadSubmissionZip(string $courseId, int $assessmentId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        if (! $this->trainerCanAccessCourse($courseId)) {
            return redirect()->route('trainer.courses')->with('error', 'You are not assigned to this training.');
        }

        $assessment = DB::table('assessment')
            ->where('assessment_id', $assessmentId)
            ->where('course_id', $courseId)
            ->whereRaw('LOWER(type) = ?', ['submission'])
            ->first();

        if (! $assessment) {
            return back()->with('error', 'Assessment not found.');
        }

        $submissionsQuery = DB::table('assessment_submission')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id');
        SharedTrainingSchema::joinNewTeacherEnrollmentIdentities($submissionsQuery);
        $submissions = $submissionsQuery
            ->where('assessment_submission.assessment_id', $assessmentId)
            ->where('enrollment.course_id', $courseId)
            ->whereNotNull('assessment_submission.submitted_at')
            ->whereNotNull('assessment_submission.submitted_file_path')
            ->select(
                'assessment_submission.submission_id',
                'assessment_submission.submitted_file_path',
                'assessment_submission.original_file_name',
                DB::raw(SharedTrainingSchema::newTeacherNameExpression().' AS participant_name')
            )
            ->orderBy('participant_name')
            ->get();

        if ($submissions->isEmpty()) {
            return back()->with('error', 'No files submitted for this task yet.');
        }

        $zipFileName = Str::slug($assessment->title).'_submissions_'.now()->format('Ymd_His').'.zip';
        $tempDir = storage_path('app/temp/submission-zips');
        $zipFullPath = $tempDir.DIRECTORY_SEPARATOR.$zipFileName;

        File::ensureDirectoryExists($tempDir);

        $zip = new \ZipArchive;
        if ($zip->open($zipFullPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP archive.');
        }

        $filesAdded = 0;
        $usedNames = [];

        foreach ($submissions as $submission) {
            $storedPath = (string) $submission->submitted_file_path;
            $sourcePath = $this->submissionFiles->resolve($storedPath);
            if ($sourcePath === null || ! $this->submissionFiles->exists($storedPath)) {
                continue;
            }

            $safeParticipant = Str::slug($submission->participant_name ?: 'unknown') ?: 'unknown';
            $safeFileName = $this->submissionFiles->safeFileName($submission->original_file_name, $storedPath);
            $entryName = $safeParticipant.'__'.$safeFileName;

            $suffix = 1;
            $base = $entryName;
            while (isset($usedNames[$entryName])) {
                $info = pathinfo($base);
                $entryName = $info['filename'].'_'.$suffix
                    .(isset($info['extension']) ? '.'.$info['extension'] : '');
                $suffix++;
            }
            $usedNames[$entryName] = true;

            $zip->addFile($sourcePath, $entryName);
            $filesAdded++;
        }

        $zip->close();

        if ($filesAdded === 0) {
            File::delete($zipFullPath);

            return back()->with('error', 'No actual files exist on disk for these submissions. The records exist but the uploads may have been removed.');
        }

        return response()->download($zipFullPath)->deleteFileAfterSend(true);
    }

    private function submissionFile(string $courseId, int $submissionId): ?object
    {
        $query = DB::table('assessment_submission')
            ->join('assessment', 'assessment_submission.assessment_id', '=', 'assessment.assessment_id')
            ->join('enroll_guru_baru as enrollment', 'assessment_submission.enrollment_id', '=', 'enrollment.enrollment_id');
        SharedTrainingSchema::joinNewTeacherEnrollmentIdentities($query);

        return $query
            ->where('assessment_submission.submission_id', $submissionId)
            ->where('assessment.course_id', $courseId)
            ->whereRaw('LOWER(assessment.type) = ?', ['submission'])
            ->where('enrollment.course_id', $courseId)
            ->whereNotNull('assessment_submission.submitted_at')
            ->select(
                'assessment_submission.submitted_file_path',
                'assessment_submission.original_file_name',
                'assessment.title as assessment_title',
                DB::raw(SharedTrainingSchema::newTeacherNameExpression().' AS participant_name')
            )
            ->first();
    }

    private function trainerCanAccessCourse(string $courseId): bool
    {
        return SharedTrainingSchema::trainerAssignedToCourse(
            SharedTrainingSchema::currentTrainerId(),
            $courseId
        );
    }
}
