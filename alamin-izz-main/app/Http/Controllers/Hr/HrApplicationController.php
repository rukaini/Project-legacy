<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $tab    = $request->query('tab', 'all');
        $status = $request->query('status', 'pending');
        $search = $request->query('search', '');

        $outsiderQuery = EnrollOutsider::with(['outsider', 'course'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('outsider', fn ($q2) =>
                $q2->where('name', 'like', "%{$search}%")
            ))
            ->latest('applied_at');

        $guruBaruQuery = EnrollGuruBaru::with(['applicant', 'course'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('applicant', fn ($q2) =>
                $q2->where('gn_name', 'like', "%{$search}%")
            ))
            ->latest('applied_at');

        $outsiders = $outsiderQuery->get()->map(fn ($e) => [
            'id'              => $e->outsider_id . '-' . $e->courseID,
            'type'            => 'outsider',
            'outsider_id'     => $e->outsider_id,
            'courseID'       => $e->courseID,
            'name'            => $e->outsider?->name ?? '—',
            'ic_number'       => $e->outsider?->ICNumber ?? '—',
            'phone'           => $e->outsider?->phoneNumber ?? '—',
            'training'        => $e->course?->courseName ?? '—',
            'status'          => $e->status,
            'applied_at'      => $e->applied_at,
            'rejection_reason'=> $e->rejection_reason,
        ]);

        $guruBaru = $guruBaruQuery->get()->map(fn ($e) => [
            'id'              => 'gb-' . $e->enrollment_id,
            'type'            => 'guru_baru',
            'enrollment_id'   => $e->enrollment_id,
            'courseID'       => $e->courseID,
            'name'            => $e->applicant?->gn_name ?? '—',
            'email'           => $e->applicant?->email ?? '—',
            'phone'           => $e->applicant?->phone_number ?? '—',
            'training'        => $e->course?->courseName ?? '—',
            'status'          => $e->status,
            'applied_at'      => $e->applied_at,
            'rejection_reason'=> $e->rejection_reason,
        ]);

        return Inertia::render('hr/applications/index', [
            'outsiders'  => $tab === 'guru_baru' ? [] : $outsiders->values(),
            'guru_baru'  => $tab === 'outsider'  ? [] : $guruBaru->values(),
            'filters'    => compact('tab', 'status', 'search'),
            'counts'     => [
                'outsider_pending'  => EnrollOutsider::where('status', 'pending')->count(),
                'guru_baru_pending' => EnrollGuruBaru::where('status', 'pending')->count(),
            ],
        ]);
    }

    public function approveOutsider(Request $request, int $outsiderId, int|string $courseId): RedirectResponse
    {
        $enroll = EnrollOutsider::with(['outsider.user', 'course'])->where('outsider_id', $outsiderId)->where('course_id', $courseId)->firstOrFail();

        EnrollOutsider::where('outsider_id', $outsiderId)->where('course_id', $courseId)
            ->update(['status' => 'approved']);

        $this->notify($this->outsiderRecipient($enroll), "Your application for {$enroll->course?->courseName} has been approved.", route('applications.mine'));

        return back()->with('success', 'Outsider application approved.');
    }

    public function rejectOutsider(Request $request, int $outsiderId, int|string $courseId): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $enroll = EnrollOutsider::with(['outsider.user', 'course'])->where('outsider_id', $outsiderId)->where('course_id', $courseId)->firstOrFail();

        EnrollOutsider::where('outsider_id', $outsiderId)->where('course_id', $courseId)
            ->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);

        $this->notify($this->outsiderRecipient($enroll), "Your application for {$enroll->course?->courseName} has been rejected.", route('applications.mine'));

        return back()->with('success', 'Outsider application rejected.');
    }

    public function approveGuruBaru(EnrollGuruBaru $enrollment): RedirectResponse
    {
        $enrollment->load(['applicant', 'course']);
        $enrollment->update(['status' => 'approved']);

        $this->notify(['gn_id' => $enrollment->gn_id], "Your application for {$enrollment->course?->courseName} has been approved.", route('guru_baru.applications.mine'));

        return back()->with('success', 'Guru Baru application approved.');
    }

    public function rejectGuruBaru(Request $request, EnrollGuruBaru $enrollment): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $enrollment->load(['applicant', 'course']);
        $enrollment->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);

        $this->notify(['gn_id' => $enrollment->gn_id], "Your application for {$enrollment->course?->courseName} has been rejected.", route('guru_baru.applications.mine'));

        return back()->with('success', 'Guru Baru application rejected.');
    }

    /** Outsiders are local accounts, so they're addressed by user_id. */
    private function outsiderRecipient(EnrollOutsider $enroll): array
    {
        $user = $enroll->outsider?->user;

        return $user ? Notification::identityOf($user) : [];
    }

    private function notify(array $to, string $message, string $url): void
    {
        Notification::send($to, $message, 'application.decision', $url);
    }
}
