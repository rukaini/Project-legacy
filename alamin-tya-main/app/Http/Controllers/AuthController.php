<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Models\Teacher;
use App\Models\Staff;
use App\Models\Principal;
use App\Models\HrAdministrator;

class AuthController extends Controller
{
    /**
     * The old local login is retired. Anyone who lands on /login is sent to Al Amin Login.
     */
    public function showLogin()
    {
        $portal = (string) config('gateway.portal_url');

        // Guard against a redirect loop if the .env values are missing.
        abort_if($portal === '', 503, 'Al Amin Login is not configured for this system.');

        return redirect()->away($portal);
    }

    private function getUserId($user)
    {
        $role = strtolower($user->role);
        return match($role) {
            'teacher' => $user->teacherID ?? null,
            'staff' => $user->staffID ?? null,
            'principal' => $user->principalID ?? null,
            'hr' => $user->hrid ?? null,
            'hr_administrator' => $user->hrid ?? null,
            default => null,
        };
    }

    private function getUserName($user)
    {
        $role = strtolower($user->role);
        return match($role) {
            'teacher' => $user->teacherName ?? null,
            'staff' => $user->staffName ?? null,
            'principal' => $user->principalName ?? null,
            'hr' => $user->username ?? null,
            'hr_administrator' => $user->username ?? null,
            default => null,
        };
    }

    public function showChangePassword()
    {
        if (!Session::has('user')) {
            return redirect()->route('login');
        }
        return view('auth.change-password');
    }

public function changePassword(Request $request)
{
    $request->validate([
        'password' => 'required|min:8|confirmed'
    ]);

    $user = Session::get('user');
    $role = strtolower(Session::get('role'));
    $email = Session::get('email');

    $record = null;
    
    switch($role) {
        case 'teacher':
            $record = Teacher::where('email', $email)->first();
            break;
        case 'staff':
            $record = Staff::where('email', $email)->first();
            break;
        case 'principal':
            $record = Principal::where('email', $email)->first();
            break;
        case 'hr':
        case 'hr_administrator':
            $record = HrAdministrator::where('email', $email)->first();
            break;
        default:
            $record = null;
    }

    if (!$record) {
        return back()->with('error', 'User record could not be found.');
    }

    // Hash the password manually (since we removed the mutator)
    $record->password = Hash::make($request->password);
    $record->password_change_required = 0;
    $record->save();

    // Refresh and update session with fresh data
    $record->refresh();
    Session::put('user', $record);
    Session::put('userID', $this->getUserId($record));
    Session::put('userName', $this->getUserName($record));
    Session::put('role', strtolower($record->role));
    Session::put('email', $record->email);
    
    if (isset($record->schoolID)) {
        Session::put('schoolID', $record->schoolID);
    }

    return $this->redirectByRole($record->role)->with('success', 'Password updated successfully');
}

    private function redirectByRole($role)
    {
        $role = strtolower($role);
        return match($role) {
            'teacher' => redirect()->route('teacher.dashboard'),
            'principal' => redirect()->route('principal.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            'hr' => redirect()->route('hr.dashboard'),
            'hr_administrator' => redirect()->route('hr.dashboard'),
            default => redirect()->route('login')->with('error', 'Invalid role: ' . $role),
        };
    }

    /**
     * Destroy ONLY this system's session, then go back to Al Amin Login.
     * Al Amin Login's own session is left alone so the user can pick another system.
     */
    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectToPortal();
    }

    private function redirectToPortal()
    {
        $portal = (string) config('gateway.portal_url');

        if ($portal !== '' && filter_var($portal, FILTER_VALIDATE_URL)) {
            return redirect()->away($portal);
        }

        return redirect()->route('login');
    }
}