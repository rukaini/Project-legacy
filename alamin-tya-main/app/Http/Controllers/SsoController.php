<?php

namespace App\Http\Controllers;

use App\Exceptions\SsoException;
use App\Services\GatewaySsoClient;
use App\Services\HrmsSsoAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SsoController extends Controller
{
    public function callback(
        Request $request,
        GatewaySsoClient $gateway,
        HrmsSsoAuthenticator $authenticator,
    ): RedirectResponse {
        $code = (string) $request->query('code', '');

        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->failure($request, 'This sign-in link is invalid or incomplete.');
        }

        try {
            $identity = $gateway->redeem($code);
            $local = $authenticator->resolve($identity);
        } catch (SsoException $exception) {
            return $this->failure($request, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure($request, 'We could not complete single sign-on right now. Please try again.');
        }

        if (! $local) {
            return $this->failure($request, 'Your verified account is not active or authorized for this system.');
        }

        $dashboard = (string) $local['dashboard'];
        unset($local['dashboard']);
        $local['auth_source'] = 'sso';

        // Fresh session for this system only (does not touch Al Amin Login's session).
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put($local);

        return redirect()->route($dashboard);
    }

    private function failure(Request $request, string $message): RedirectResponse
    {
        Log::warning('SSO callback failed: '.$message);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // route('login') redirects to Al Amin Login - the old local login is gone.
        return redirect()->route('login')->with('error', $message);
    }
}
