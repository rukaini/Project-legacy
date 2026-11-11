<?php

namespace App\Http\Controllers;

use App\Services\AdministratorAccountService;
use App\Services\CanonicalIdentityService;
use App\Services\SystemRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewayAuthController extends Controller
{
    public function login(Request $request): View|RedirectResponse
    {
        return $request->session()->has('gateway.identity') ? redirect()->route('systems.index') : view('auth.login');
    }

    public function authenticate(
        Request $request,
        CanonicalIdentityService $identities,
        AdministratorAccountService $administrator,
    ): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);
        $roles = $identities->authenticate($credentials['email'], $credentials['password']);
        if ($roles === []) {
            return back()->withErrors(['email' => 'The email or password is incorrect, or this account is not active.'])->onlyInput('email');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $first = reset($roles);
        $request->session()->put('gateway.identity', [
            'email' => strtolower(trim($credentials['email'])),
            'name' => (string) ($first['name'] ?? $credentials['email']),
            'roles' => $roles,
            'selected_role' => count($roles) === 1 ? array_key_first($roles) : null,
            'is_administrator' => $administrator->matches($credentials['email']),
        ]);

        return redirect()->route('systems.index');
    }

    public function roles(): RedirectResponse
    {
        return redirect()->route('systems.index');
    }

    public function selectRole(Request $request, SystemRegistry $registry): RedirectResponse
    {
        $request->validate(['role' => ['required', 'string', 'max:40']]);
        $identity = $request->session()->get('gateway.identity');
        $role = (string) $request->input('role');
        abort_unless(is_array($identity) && isset($identity['roles'][$role]) && $registry->role($role), 403);
        $identity['selected_role'] = $role;
        $identity['name'] = $identity['roles'][$role]['name'] ?: $identity['name'];
        $request->session()->put('gateway.identity', $identity);

        return redirect()->route('systems.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have signed out of ELIT Portal.');
    }
}
