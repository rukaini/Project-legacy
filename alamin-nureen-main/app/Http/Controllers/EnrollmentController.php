<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnrollmentController extends Controller
{
    private function staff(): object
    {
        $id = trim((string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? ''));
        abort_if($id === '', 403, 'Staff EDU login required.');
        $staff = DB::table('staff_edu')->where('staffID', $id)->first();
        abort_unless($staff && strtolower(trim((string) $staff->status)) === 'active'
            && in_array(strtolower(str_replace([' ', '-'], '_', (string)$staff->role)), ['staff_edu','admin'], true), 403);
        return $staff;
    }

    public function enrollTraining(string $courseID): RedirectResponse
    {
        $staff = $this->staff();
        abort_unless(Schema::hasTable('trainhub_staff_enrollments'), 503, 'Install the enrollment database patch first.');
        $course = DB::table('course')->where('courseID', $courseID)->first();
        abort_unless($course, 404);
        if (!in_array(strtolower((string)$course->status), ['upcoming','ongoing'], true)) {
            return back()->with('error', 'Only upcoming or ongoing training is open for enrollment.');
        }
        if ($course->closeDate && $course->closeDate < date('Y-m-d')) {
            return back()->with('error', 'Enrollment for this training is already closed.');
        }
        try {
            $result = DB::transaction(function () use ($course, $staff) {
                $existing = DB::table('course_participant')->where('courseID', $course->courseID)
                    ->where('participantType', 'staff')->where('staffID', $staff->staffID)->exists();
                if ($existing) return 'already';
                $count = DB::table('course_participant')->where('courseID', $course->courseID)->count();
                if ($course->capacity && $count >= (int)$course->capacity) return 'full';
                $id = 'P'.strtoupper(substr(sha1('staff|'.$staff->staffID.'|'.$course->courseID), 0, 19));
                DB::table('trainhub_staff_enrollments')->insertOrIgnore([
                    'staffID' => $staff->staffID, 'courseID' => $course->courseID, 'enrolled_at' => now(),
                ]);
                DB::table('course_participant')->insert([
                    'participantID' => $id, 'participantType' => 'staff',
                    'participantName' => $staff->staffName, 'organisationName' => 'Al Amin Edu Oasis',
                    'ICNumber' => $staff->ICNumber, 'phoneNumber' => $staff->phoneNumber,
                    'email' => $staff->email, 'staffID' => $staff->staffID,
                    'courseID' => $course->courseID, 'RSVPStatus' => 'Will come',
                ]);
                return 'joined';
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) return back()->with('error', 'You are already enrolled in this training.');
            throw $e;
        }
        if ($result === 'full') return back()->with('error', 'Training capacity has been reached.');
        if ($result === 'already') return back()->with('error', 'You are already included in the participant list (Staff/PIC).');
        $this->audit((string)$staff->staffID, 'trainhub_staff_enrollments', 'Staff self-enrolled in training '.$courseID);
        return back()->with('success', 'Training enrollment saved. You are a Staff Participant unless you are assigned as PIC.');
    }

    public function verifyOrder(Request $request): RedirectResponse
    {
        $reviewer = $this->staff();
        abort_unless(Schema::hasTable('trainhub_staff_training_orders'), 503, 'Install the management-order database patch first.');
        $data = $request->validate([
            'courseID' => ['required', 'string', 'max:20'],
            'staffID' => ['required', 'string', 'max:20'],
            'decision' => ['required', 'in:approved,revoked'],
            'order_reference' => ['required', 'string', 'min:5', 'max:150'],
            'verified_document' => ['accepted'],
        ]);
        if ($data['staffID'] === $reviewer->staffID) {
            return back()->with('error', 'You cannot verify your own management order. Another Staff EDU must review it.');
        }
        $person = DB::table('course_participant')->where('participantType', 'staff')
            ->where('courseID', $data['courseID'])->where('staffID', $data['staffID'])->exists();
        abort_unless($person, 422, 'The selected staff member is not enrolled in this training.');
        $course = DB::table('course')->where('courseID', $data['courseID'])->first();
        abort_unless($course, 404);
        if (stripos((string)$course->organiserName, 'Al Amin Edu Oasis') !== false) {
            return back()->with('error', 'Management-order verification is only needed for other organisers.');
        }
        DB::table('trainhub_staff_training_orders')->updateOrInsert(
            ['courseID' => $data['courseID'], 'staffID' => $data['staffID']],
            ['decision' => $data['decision'], 'order_reference' => trim($data['order_reference']),
                'approved_by' => $reviewer->staffID, 'reviewed_at' => now()]
        );
        $this->audit((string)$reviewer->staffID, 'trainhub_staff_training_orders',
            'Order '. $data['decision'].' for '.$data['staffID'].' / '.$data['courseID'].', reference '.$data['order_reference']);
        // Attendance remains independently required before credit is earned.
        try { \App\Services\StaffCreditHourService::refreshCache($data['staffID']); }
        catch (\Throwable $e) { report($e); }
        return back()->with('success', 'Management order decision saved with reviewer and reference.');
    }

    private function audit(string $id, string $table, string $message): void
    {
        DB::table('audit_log')->insert([
            'staffID' => $id, 'userName' => (string) ($_SESSION['staffName'] ?? $id),
            'actionType' => 'INSERT', 'tableName' => $table, 'newValue' => $message,
        ]);
    }
}
