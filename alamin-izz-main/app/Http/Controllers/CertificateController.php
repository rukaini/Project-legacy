<?php

namespace App\Http\Controllers;

use App\Http\Requests\Certificate\ReviewCertificateRequest;
use App\Http\Requests\Certificate\StoreCertificateRequest;
use App\Http\Requests\Certificate\UpdateCertificateRequest;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\PenyelarasLadap;
use App\Models\School;
use App\Models\Teacher;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function __construct(
        private CertificateService $service,
        private TeacherRepositoryInterface $teachers,
        private PrincipalRepositoryInterface $principals,
    ) {}

    public function index(Request $request): Response
    {
        $me = $this->teachers->findByEmail($request->user()->email);

        $certificates = $me
            ? PenyelarasLadap::certificates()
                ->where('assigned_teacher_id', $me->id)
                ->orderByDesc('session_date')
                ->get()
            : collect();

        return Inertia::render('certificates/index', [
            'certificates' => $certificates->map(fn (PenyelarasLadap $c) => $this->present($c))->values(),
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $me = $this->teachers->findByEmail($request->user()->email);

        try {
            abort_unless($me, 403, 'No teacher profile found for your account.');
            $this->service->store($request->user(), $me, $request->validated(), $request->file('certificate'));
            return redirect()->route('certificates.index')->with('success', 'Certificate uploaded.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** Guru Besar: teacher cards for their school, or one teacher's certificates when ?teacher_id is given. */
    public function school(Request $request): Response
    {
        $schoolId  = $this->principals->findByEmail($request->user()->email)?->schoolID;
        $teacherId = $request->query('teacher_id');

        $certificateCounts = $schoolId
            ? PenyelarasLadap::certificates()
                ->where('schoolID', $schoolId)
                ->selectRaw('assigned_teacher_id, COUNT(*) as total')
                ->groupBy('assigned_teacher_id')
                ->pluck('total', 'assigned_teacher_id')
            : collect();

        $teachers = $schoolId
            ? Teacher::whereHas('assigns', fn ($q) => $q->where('schoolID', $schoolId)->active())
                ->orderBy('teacherName')
                ->get()
                ->map(fn (Teacher $t) => [
                    'id'                => $t->teacherID,
                    'name'              => $t->teacherName,
                    'email'             => $t->email,
                    'certificate_count' => (int) ($certificateCounts[$t->teacherID] ?? 0),
                ])
                ->values()
            : collect();

        $selectedTeacher = $teacherId ? $teachers->firstWhere('id', $teacherId) : null;

        $certificates = $selectedTeacher
            ? PenyelarasLadap::certificates()
                ->where('schoolID', $schoolId)
                ->where('assigned_teacher_id', $selectedTeacher['id'])
                ->orderByDesc('session_date')
                ->get()
                ->map(fn (PenyelarasLadap $c) => $this->present($c))
                ->values()
            : collect();

        return Inertia::render('certificates/school', [
            'school_name'      => $schoolId ? School::where('schoolID', $schoolId)->value('schoolName') : null,
            'teachers'         => $teachers,
            'selected_teacher' => $selectedTeacher,
            'certificates'     => $certificates,
        ]);
    }

    public function update(UpdateCertificateRequest $request, PenyelarasLadap $certificate): RedirectResponse
    {
        $me = $this->teachers->findByEmail($request->user()->email);

        try {
            abort_unless($me, 403, 'No teacher profile found for your account.');
            $this->service->update($request->user(), $certificate, $me, $request->validated(), $request->file('certificate'));
            return redirect()->route('certificates.index')->with('success', 'Certificate updated.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function review(ReviewCertificateRequest $request, PenyelarasLadap $certificate): RedirectResponse
    {
        $schoolId = $this->principals->findByEmail($request->user()->email)?->schoolID;

        try {
            $this->service->review($request->user(), $certificate, $schoolId, $request->validated());
            $verb = $request->validated('approval') === PenyelarasLadap::APPROVAL_APPROVED ? 'approved' : 'rejected';
            return back()->with('success', "Certificate {$verb}.");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Request $request, PenyelarasLadap $certificate): StreamedResponse
    {
        $user = $request->user();

        if ($user->isGuruBesar()) {
            $this->service->ensureSameSchool($certificate, $this->principals->findByEmail($user->email)?->schoolID);
        } else {
            $me = $this->teachers->findByEmail($user->email);
            abort_unless($me, 403);
            $this->service->ensureOwner($certificate, $me);
        }

        abort_unless(
            $certificate->certificate_path && Storage::disk('public')->exists($certificate->certificate_path),
            404,
            'File not found.'
        );

        $fileName = $certificate->certificate_original_name ?? basename($certificate->certificate_path);

        return Storage::disk('public')->response($certificate->certificate_path, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function destroy(Request $request, PenyelarasLadap $certificate): RedirectResponse
    {
        $me = $this->teachers->findByEmail($request->user()->email);

        try {
            abort_unless($me, 403, 'No teacher profile found for your account.');
            $this->service->delete($certificate, $me);
            return redirect()->route('certificates.index')->with('success', 'Certificate deleted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function present(PenyelarasLadap $c): array
    {
        return [
            'id'           => $c->id,
            'title'        => $c->title,
            'description'  => $c->description,
            'session_date' => $c->session_date->format('Y-m-d'),
            'start_time'   => $c->start_time,
            'end_time'     => $c->end_time,
            'location'     => $c->location,
            'file_name'    => $c->certificate_original_name,
            'has_file'     => (bool) $c->certificate_path,
            'approval'     => $c->approval,
            'credit_hours' => $c->credit_hours,
            'session_hours'=> $c->durationHours(),
            'ladap_hours'  => $c->certificateLadapHours(),
            'remarks'      => $c->remarks,
        ];
    }
}
