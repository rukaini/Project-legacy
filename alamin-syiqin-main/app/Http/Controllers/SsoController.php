<?php

namespace App\Http\Controllers;

use App\Models\StaffEdu;
use App\Models\GuruNew;
use App\Models\HRAdministrator;
use App\Models\Principal;
use App\Models\Teacher;
use App\Models\Observer;
use App\Models\ExternalObserver;
use App\Services\GatewaySsoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SsoController extends Controller
{
    public function callback(
        Request $request,
        GatewaySsoClient $gateway
    ): RedirectResponse {
        $code = (string) $request->query('code', '');

        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->failure(
                $request,
                'This sign-in link is invalid or incomplete.'
            );
        }

        try {
            $identity = $gateway->redeem($code);
            $destination = $this->authenticateUser($identity, $request);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure(
                $request,
                $exception->getMessage()
                    ?: 'We could not complete single sign-on right now.'
            );
        }

        if (! $destination) {
            return $this->failure(
                $request,
                'Your account is not active or authorized for Teacher Employment System.'
            );
        }

        $request->session()->put('auth_source', 'sso');

        return redirect()->route($destination);
    }

    private function authenticateUser(
        array $identity,
        Request $request
    ): ?string {
        $role = strtolower(trim((string) ($identity['role'] ?? '')));
        $id = (string) ($identity['id'] ?? '');
        $email = strtolower(trim((string) ($identity['email'] ?? '')));

        if ($id === '' || $email === '') {
            return null;
        }

        $this->logoutAllGuards();

        // ADMIN / STAFF EDU
        if (in_array($role, ['admin', 'staff edu', 'staff_edu'], true)) {
            $user = StaffEdu::where('staffID', $id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', 'active')
                ->first();

            if (! $user) {
                return null;
            }

            Auth::guard('admin')->login($user);
            $request->session()->regenerate();

            return 'admin.dashboard';
        }

        // NEW TEACHER
        if (in_array($role, ['new teacher', 'new_teacher'], true)) {
            $user = GuruNew::where('gn_id', $id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('current_status', 'Active')
                ->first();

            if (! $user) {
                return null;
            }

            Auth::guard('new_teacher')->login($user);
            $request->session()->regenerate();

            return 'new_teacher.dashboard';
        }

        // HR
        if ($role === 'hr') {
            $user = HRAdministrator::where('hrid', $id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $user) {
                return null;
            }

            Auth::guard('hr')->login($user);
            $request->session()->regenerate();

            return 'hr.dashboard';
        }

        // PRINCIPAL
        if ($role === 'principal') {
            $user = Principal::where('principalID', $id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if (! $user) {
                return null;
            }

            Auth::guard('principal')->login($user);
            $request->session()->regenerate();

            return 'principal.dashboard';
        }

        // OBSERVER
        if ($role === 'observer') {
            $teacher = Teacher::whereRaw(
                'LOWER(email) = ?',
                [$email]
            )->first();

            if (! $teacher) {
                return null;
            }

            $observer = Observer::where(
                'teacherID',
                $teacher->teacherID
            )
                ->where('status', 'active')
                ->first();

            if (! $observer) {
                return null;
            }

            Auth::guard('teacher')->login($teacher);
            $request->session()->regenerate();

            $request->session()->put([
                'teacher_role' => 'observer',
                'observer_id' => $observer->observerID,
            ]);

            return 'observer.dashboard';
        }

        // EXTERNAL OBSERVER
        if (in_array(
            $role,
            ['external observer', 'external_observer'],
            true
        )) {
            $teacher = Teacher::whereRaw(
                'LOWER(email) = ?',
                [$email]
            )->first();

            if (! $teacher) {
                return null;
            }

            $externalObserver = ExternalObserver::where(
                'teacherID',
                $teacher->teacherID
            )
                ->where('status', 'active')
                ->first();

            if (! $externalObserver) {
                return null;
            }

            Auth::guard('teacher')->login($teacher);
            $request->session()->regenerate();

            $request->session()->put([
                'teacher_role' => 'external_observer',
                'external_observer_id' => $externalObserver->externalObserverID,
            ]);

            return 'external.dashboard';
        }

        return null;
    }

    private function logoutAllGuards(): void
    {
        foreach (
            [
                'admin',
                'hr',
                'new_teacher',
                'principal',
                'teacher',
            ] as $guard
        ) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }
    }

    private function failure(
        Request $request,
        string $message
    ): RedirectResponse {
        $this->logoutAllGuards();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', $message);
    }
}