<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Services\Feedback\TrainerFeedbackReportService;
use App\Support\SharedTrainingSchema;
use App\Support\TrainerActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Str;

class ProfileController extends Controller
{
    public function __construct(
        private readonly TrainerFeedbackReportService $feedbackReport
    ) {}

    public function profile()
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerReference = (string) session('user_id');
        $trainerId = SharedTrainingSchema::currentTrainerId();

        $trainer = SharedTrainingSchema::trainer($trainerReference);
        $liveRating = $this->feedbackReport->liveRating($trainerReference);
        $certificateTableExists = Schema::hasTable('trainer_certificate');

        $certificates = collect();
        if ($certificateTableExists) {
            $certificates = DB::table('trainer_certificate')
                ->where('trainer_id', $trainerId)
                ->orderByDesc('uploaded_at')
                ->get();
        }
        if (! $trainer) {
            return redirect()->route('login')->with('error', 'Trainer account not found.');
        }

        // Assigned trainings (two-trainer rule: courses where this trainer runs a session)
        $assignedTrainingCount = SharedTrainingSchema::assignedCourseIds($trainerReference)->count();

        // Proposals submitted by this trainer
        $proposalCount = Schema::hasTable('course_proposal')
            ? DB::table('course_proposal')->where('trainer_id', $trainerId)->count()
            : 0;

        // Documents sent to this trainer by the admin
        $documentTableExists = Schema::hasTable('trainer_document');
        $documents = collect();
        $documentCount = 0;

        if ($documentTableExists) {
            $documentTrainerId = $trainer->trainer_id;
            $documents = DB::table('trainer_document')
                ->where('trainerID', $documentTrainerId)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($doc) {
                    // Build a usable URL from file_path (full URL passes through untouched).
                    $path = $doc->file_path ?? null;
                    $doc->file_url = $path
                        ? (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://']) ? $path : asset($path))
                        : null;

                    return $doc;
                });
            $documentCount = $documents->count();
        }

        return view('trainer.profile', [
            'trainer' => $trainer,
            'liveRating' => $liveRating,
            'assignedTrainingCount' => $assignedTrainingCount,
            'proposalCount' => $proposalCount,
            'documents' => $documents,
            'documentCount' => $documentCount,
            'documentTableExists' => $documentTableExists,
            'activeTab' => request('tab', 'profile'),
            'certificates' => $certificates,
            'certificateTableExists' => $certificateTableExists,
        ]);
    }

    /**
     * POST /trainer/profile/password
     * Change the trainer's password and write an activity-log entry.
     */
    public function updatePassword(Request $request)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerReference = (string) session('user_id');

        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'new_password.confirmed' => 'The new password confirmation does not match.',
            'new_password.different' => 'Your new password must be different from your current one.',
            'new_password.min' => 'The new password must be at least 8 characters.',
        ]);

        $trainerQuery = DB::table('trainer');
        SharedTrainingSchema::constrainTrainer($trainerQuery, $trainerReference);
        $trainer = $trainerQuery->first(['trainerPassword']);
        if (! $trainer) {
            return redirect()->route('login')->with('error', 'Trainer account not found.');
        }

        $stored = (string) $trainer->trainerPassword;
        $matches = Hash::isHashed($stored) && Hash::check($request->current_password, $stored);

        if (! $matches) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->withInput();
        }

        $update = DB::table('trainer');
        SharedTrainingSchema::constrainTrainer($update, $trainerReference);
        $update->update([
            'trainerPassword' => Hash::make($request->new_password),
        ]);

        // Activity log (admin/EDU side reads this)
        TrainerActivityLogger::log($trainerReference, 'profile.password', 'Changed account password');

        return back()->with('success', 'Your password has been updated successfully.');
    }

    public function updatePicture(Request $request)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerReference = (string) session('user_id');

        $request->validate([
            'trainer_pic' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'trainer_pic.required' => 'Please choose an image to upload.',
            'trainer_pic.image' => 'The file must be an image.',
            'trainer_pic.mimes' => 'Use a JPG, PNG or WEBP image.',
            'trainer_pic.max' => 'The image must be 2 MB or smaller.',
        ]);

        $trainer = SharedTrainingSchema::trainer($trainerReference);
        if (! $trainer) {
            return redirect()->route('login')->with('error', 'Trainer account not found.');
        }

        // Save under public/trainer_pics (no storage:link needed).
        $destDir = public_path('trainer_pics');
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        $ext = strtolower($request->file('trainer_pic')->getClientOriginalExtension() ?: 'jpg');
        $fileName = 'trainer_'.$trainer->trainer_id.'_'.time().'.'.$ext;
        $request->file('trainer_pic')->move($destDir, $fileName);

        // Remove the previous local picture file, if any.
        if (! empty($trainer->trainer_pic) && ! Str::startsWith($trainer->trainer_pic, ['http://', 'https://'])) {
            $oldPath = public_path($trainer->trainer_pic);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $update = DB::table('trainer');
        SharedTrainingSchema::constrainTrainer($update, $trainerReference);
        $update->update(['trainerPic' => 'trainer_pics/'.$fileName]);

        TrainerActivityLogger::log($trainerReference, 'profile.update', 'Updated profile picture');

        return redirect()->route('trainer.profile', ['tab' => 'profile'])
            ->with('success', 'Your profile picture has been updated.');
    }

    public function pictureRemove()
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerReference = (string) session('user_id');

        $trainer = SharedTrainingSchema::trainer($trainerReference);

        if (! $trainer) {
            return redirect()->route('trainer.profile')->with('error', 'Trainer account not found.');
        }

        if (! empty($trainer->trainer_pic)) {
            Storage::disk('public')->delete($trainer->trainer_pic);
        }

        $update = DB::table('trainer');
        SharedTrainingSchema::constrainTrainer($update, $trainerReference);
        $update->update(['trainerPic' => null]);

        if (! empty($trainer->trainer_pic)) {
            TrainerActivityLogger::log($trainerReference, 'profile.update', 'Profile photo removed');
        }

        return redirect()->route('trainer.profile')->with('success', 'Profile photo removed.');
    }

    /**
     * Upload a new certificate (creates a new row in trainer_certificate).
     * A trainer can upload many certificates.
     * Route: POST /trainer/profile/certificate
     * Name:  trainer.profile.certificate
     */
    public function certificateUpload(Request $request)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $request->validate([
            'certificate' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10 MB
            'title' => 'nullable|string|max:255',
        ], [
            'certificate.required' => 'Please select a file to upload.',
            'certificate.mimes' => 'Certificate must be a PDF, DOC, DOCX, JPG, or PNG file.',
            'certificate.max' => 'Certificate must be 10 MB or smaller.',
            'title.max' => 'Title must be 255 characters or fewer.',
        ]);

        $trainerReference = (string) session('user_id');
        $trainerId = SharedTrainingSchema::currentTrainerId();

        // Make sure the trainer actually exists
        $trainerQuery = DB::table('trainer');
        SharedTrainingSchema::constrainTrainer($trainerQuery, $trainerReference);
        $trainerExists = $trainerQuery->exists();

        if (! $trainerExists) {
            return redirect()->route('trainer.profile')->with('error', 'Trainer account not found.');
        }

        // Store the new file under trainer_certs/
        $file = $request->file('certificate');
        $ext = $file->getClientOriginalExtension();
        $name = 'trainer_'.$trainerId.'_'.time().'_'.\Illuminate\Support\Str::random(6).'.'.$ext;
        $path = $file->storeAs('trainer_certs', $name, 'public');

        // Use the user-provided title if given, otherwise fall back to the original filename
        $title = trim((string) $request->input('title'));
        if ($title === '') {
            $title = $file->getClientOriginalName();
        }

        $certificateId = DB::table('trainer_certificate')->insertGetId([
            'trainer_id' => $trainerId,
            'title' => $title,
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        TrainerActivityLogger::log(
            (string) $trainerId,
            'certificate.upload',
            $title,
            null,
            'certificate',
            $certificateId
        );

        return redirect()->route('trainer.profile', ['tab' => 'certificate'])
            ->with('success', 'Certificate uploaded successfully.');
    }

    /**
     * Remove a specific certificate by ID. Verifies ownership first
     * (a trainer can only delete their own certificates).
     * Route: DELETE /trainer/profile/certificate/{certId}
     * Name:  trainer.profile.certificate.remove
     */
    public function certificateRemove($certId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainerId = SharedTrainingSchema::currentTrainerId();

        $cert = DB::table('trainer_certificate')
            ->where('cert_id', $certId)
            ->where('trainer_id', $trainerId) // ownership check
            ->first();

        if (! $cert) {
            return redirect()->route('trainer.profile', ['tab' => 'certificate'])
                ->with('error', 'Certificate not found or you do not have permission to remove it.');
        }

        // Delete the file from storage
        if (! empty($cert->file_path)) {
            Storage::disk('public')->delete($cert->file_path);
        }

        // Delete the row
        DB::table('trainer_certificate')
            ->where('cert_id', $certId)
            ->delete();

        TrainerActivityLogger::log(
            (string) $trainerId,
            'certificate.remove',
            $cert->title ?: 'Certificate',
            null,
            'certificate',
            (int) $certId
        );

        return redirect()->route('trainer.profile', ['tab' => 'certificate'])
            ->with('success', 'Certificate removed.');
    }

    public function documentView($docId)
    {
        if (session('user_role') !== 'trainer') {
            return redirect()->route('login')->with('error', 'Please login as trainer first.');
        }

        $trainer = SharedTrainingSchema::trainer((string) session('user_id'));
        $trainerId = $trainer?->trainer_id;

        // Find the document, with ownership check
        $doc = DB::table('trainer_document')
            ->where('document_id', $docId)
            ->where('trainerID', $trainerId)
            ->first();

        if (! $doc) {
            return redirect()->route('trainer.profile', ['tab' => 'documents'])
                ->with('error', 'Document not found.');
        }

        if (empty($doc->file_path)) {
            return redirect()->route('trainer.profile', ['tab' => 'documents'])
                ->with('error', 'No file attached to this document.');
        }

        // Mark as read (only update if it wasn't already, to avoid touching the row unnecessarily)
        if (empty($doc->is_read)) {
            DB::table('trainer_document')
                ->where('document_id', $docId)
                ->update([
                    'is_read' => 1,
                ]);
        }

        // Send the trainer straight to the file
        return redirect(asset('storage/'.$doc->file_path));
    }
}
