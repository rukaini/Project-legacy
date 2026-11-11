<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use App\Models\School;
use App\Services\Training\TrainingProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class GuruNewController extends Controller
{
    public function index(
        Request $request,
        TrainingProgressService $trainingProgressService
    ) {
        // Synchronize completion status for Active teachers only.
        // Inactive teachers will remain Inactive.
        GuruNew::where('current_status', 'Active')
            ->select('gn_id', 'current_status')
            ->chunkById(
                100,
                function ($teachers) use ($trainingProgressService) {
                    foreach ($teachers as $teacher) {
                        $this->syncCompletionStatus(
                            $teacher,
                            $trainingProgressService
                        );
                    }
                },
                'gn_id'
            );

        // Get filter values
        $search = trim((string) $request->search);
        $schoolID = $request->schoolID;
        $status = $request->status;
        $appointedDate = $request->appointed_date;

        // Get new teachers
        $guruNews = GuruNew::with('school')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('gn_name', 'like', "%{$search}%")
                        ->orWhere('gn_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('ic_number', 'like', "%{$search}%");
                });
            })
            ->when($schoolID, function ($query, $schoolID) {
                $query->where('schoolID', $schoolID);
            })
            ->when($status, function ($query, $status) {
                $query->where('current_status', $status);
            })
            ->when($appointedDate, function ($query, $appointedDate) {
                $query->whereDate('appointed_date', $appointedDate);
            })
            ->orderBy('gn_name')
            ->paginate(10)
            ->withQueryString();

        // Get schools for filter and manage modal
        $schools = School::orderBy('schoolName')->get();

        return view(
            'hr.gurunew.index',
            compact('guruNews', 'schools')
        );
    }

    public function create()
    {
        $schools = School::where('vacancy', '>', 0)
            ->orderBy('schoolName')
            ->get();

        return view('hr.gurunew.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ic_number' => [
                'required',
                'digits:12',
                'unique:guru_new,ic_number',
            ],
            'gn_name' => [
                'required',
                'string',
                'max:255',
            ],
            'phone_number' => [
                'required',
                'string',
                'max:20',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:guru_new,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
            'appointed_date' => [
                'required',
                'date',
            ],
            'schoolID' => [
                'required',
                'exists:school,schoolID',
            ],
        ]);

        $ic = $request->ic_number;
        $lastDigit = (int) substr($ic, -1);

        $gender = $lastDigit % 2 === 0
            ? 'Female'
            : 'Male';

        DB::statement(
            'CALL sp_register_guru_new(?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $request->ic_number,
                $request->gn_name,
                $request->phone_number,
                $request->email,
                $gender,
                Hash::make($request->password),
                $request->appointed_date,
                Auth::guard('hr')->id(),
                $request->schoolID,
            ]
        );

        return redirect()
            ->route('hr.gurunew.index')
            ->with('success', 'New Teacher registered successfully.');
    }

    public function update(
        Request $request,
        $gn_id,
        TrainingProgressService $trainingProgressService
    ) {
        $guru = GuruNew::findOrFail($gn_id);

        // HR cannot manually assign Complete.
        // Existing Complete teachers may remain Complete
        // while HR edits their details.
        $allowedStatuses = $guru->current_status === 'Complete'
            ? ['Complete', 'Inactive']
            : ['Active', 'Inactive'];

        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'max:20',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('guru_new', 'email')
                    ->ignore($gn_id, 'gn_id'),
            ],
            'schoolID' => [
                'required',
                'exists:school,schoolID',
            ],
            'appointed_date' => [
                'required',
                'date',
            ],
            'current_status' => [
                'required',
                Rule::in($allowedStatuses),
            ],
        ]);

        // Update teacher details
        $guru->phone_number = $validated['phone_number'];
        $guru->email = $validated['email'];
        $guru->schoolID = $validated['schoolID'];
        $guru->appointed_date = $validated['appointed_date'];

        // Set selected status.
        // Complete can only be retained if already Complete.
        $guru->current_status = $validated['current_status'];

        // Automatically Complete if Active and
        // all requirements are fulfilled.
        if (
            $guru->current_status === 'Active' &&
            $this->hasCompletedAllRequirements(
                $guru->gn_id,
                $trainingProgressService
            )
        ) {
            $guru->current_status = 'Complete';
        }

        // Inactive remains Inactive.
        $guru->save();

        return redirect()
            ->route('hr.gurunew.index')
            ->with('success', 'New Teacher details updated successfully.');
    }

    private function syncCompletionStatus(
        GuruNew $guru,
        TrainingProgressService $trainingProgressService
    ): void {
        if ($guru->current_status !== 'Active') {
            return;
        }

        if (!$this->hasCompletedAllRequirements(
            $guru->gn_id,
            $trainingProgressService
        )) {
            return;
        }

        // Never override Inactive status.
        GuruNew::where('gn_id', $guru->gn_id)
            ->where('current_status', 'Active')
            ->update([
                'current_status' => 'Complete',
            ]);
    }

    private function hasCompletedAllRequirements(
        string $gnId,
        TrainingProgressService $trainingProgressService
    ): bool {
        // 1. Observation must be completed.
        if (!$this->observationCompleted($gnId)) {
            return false;
        }

        // 2. All LMS courses and feedback must be completed.
        $courses = $trainingProgressService->getProgress($gnId);

        if (count($courses) === 0) {
            return false;
        }

        foreach ($courses as $course) {
            if (!($course['completed'] ?? false)) {
                return false;
            }

            if (!($course['feedback_completed'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    private function observationCompleted(string $gnId): bool
    {
        // PRE must be submitted.
        $preSubmitted = DB::table('pre_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'PRE')
            ->where('status', 'Submitted')
            ->exists();

        if (!$preSubmitted) {
            return false;
        }

        // Get submitted EXTERNAL attempts.
        $externalAttempts = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'EXTERNAL')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        $hasRepeatWithFeedback = false;

        foreach ($externalAttempts as $external) {
            $feedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'EXTERNAL')
                ->where('externalObserverID', $external->externalObserverID)
                ->where('attempt_no', $external->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if (!$feedbackSubmitted) {
                continue;
            }

            $result = $this->getObservationResult($external);

            // EXTERNAL PASS + matching feedback.
            if ($result === 'PASS') {
                return true;
            }

            if ($result === 'REPEAT') {
                $hasRepeatWithFeedback = true;
            }
        }

        // POST requires EXTERNAL REPEAT with feedback.
        if (!$hasRepeatWithFeedback) {
            return false;
        }

        // Get submitted POST attempts.
        $postAttempts = DB::table('pdpc_response')
            ->where('gn_id', $gnId)
            ->where('observation_stage', 'POST')
            ->where('status', 'Submitted')
            ->orderByDesc('attempt_no')
            ->orderByDesc('responseID')
            ->get();

        foreach ($postAttempts as $post) {
            if ($this->getObservationResult($post) !== 'PASS') {
                continue;
            }

            $feedbackSubmitted = DB::table('post_response')
                ->where('gn_id', $gnId)
                ->where('observation_stage', 'POST')
                ->where('observerID', $post->observerID)
                ->where('attempt_no', $post->attempt_no)
                ->where('status', 'Submitted')
                ->exists();

            if ($feedbackSubmitted) {
                return true;
            }
        }

        return false;
    }

    private function getObservationResult($response): ?string
    {
        $result = strtoupper(trim(
            (string) ($response->result ?? '')
        ));

        if (in_array($result, ['PASS', 'REPEAT'], true)) {
            return $result;
        }

        if ($response->percentage === null) {
            return null;
        }

        return (float) $response->percentage >= 85
            ? 'PASS'
            : 'REPEAT';
    }
}
