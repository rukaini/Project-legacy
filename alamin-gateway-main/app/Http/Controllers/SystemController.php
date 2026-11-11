<?php

namespace App\Http\Controllers;

use App\Services\CanonicalIdentityService;
use App\Services\HandoffService;
use App\Services\SystemRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemController extends Controller
{
    public function index(Request $request, SystemRegistry $registry): View
    {
        $identity = $request->session()->get('gateway.identity');
        $roleKey = $identity['selected_role'] ?? null;
        $roles = [];
        foreach ($identity['roles'] ?? [] as $key => $roleIdentity) {
            if ($configuredRole = $registry->role($key)) {
                $roles[$key] = $configuredRole + ['identity' => $roleIdentity];
            }
        }

        $role = is_string($roleKey) && isset($roles[$roleKey]) ? $roles[$roleKey] : null;
        $systems = $role ? $registry->systemsFor($roleKey) : [];

        return view('systems.index', compact('identity', 'roleKey', 'role', 'roles', 'systems'));
    }

    public function launch(
        Request $request,
        string $system,
        SystemRegistry $registry,
        HandoffService $handoffs,
        CanonicalIdentityService $identities,
    ): RedirectResponse {
        $sessionIdentity = $request->session()->get('gateway.identity');
        $role = (string) ($sessionIdentity['selected_role'] ?? '');
        abort_unless($registry->allows($role, $system), 403);
        $identity = $sessionIdentity['roles'][$role] ?? null;
        abort_unless(is_array($identity), 403);

        $identity = $identities->revalidate($identity);
        if (! is_array($identity)) {
            unset($sessionIdentity['roles'][$role]);
            if (($sessionIdentity['roles'] ?? []) === []) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } else {
                $sessionIdentity['selected_role'] = null;
                $request->session()->put('gateway.identity', $sessionIdentity);
            }

            abort(403, 'This role is no longer eligible. Please sign in again.');
        }
        $sessionIdentity['roles'][$role] = $identity;
        $request->session()->put('gateway.identity', $sessionIdentity);

        $callback = $registry->callbackUrl($system);
        $scheme = strtolower((string) parse_url($callback, PHP_URL_SCHEME));
        if ($callback === '/' || ! filter_var($callback, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            return back()->withErrors(['system' => 'This system is temporarily unavailable. Please contact your administrator.']);
        }

        if ((bool) config('sso.require_https') && $scheme !== 'https') {
            return back()->withErrors(['system' => 'This system is not securely configured yet. Please contact your administrator.']);
        }

        $code = $handoffs->issue($system, $identity);

        return redirect()->away($callback.'?'.http_build_query(['code' => $code], '', '&', PHP_QUERY_RFC3986));
    }
}
