<?php

namespace App\Http\Controllers;

use App\Exceptions\SsoException;
use App\Services\GatewaySsoClient;
use App\Services\SsoAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SsoController extends Controller
{
    public function callback(
        Request $request,
        GatewaySsoClient $gateway,
        SsoAuthenticator $authenticator,
    ): RedirectResponse {
        $code = (string) $request->query('code', '');
        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->failure($request, 'This sign-in link is invalid or incomplete.');
        }

        try {
            $user = $authenticator->resolve($gateway->redeem($code));
        } catch (SsoException $exception) {
            return $this->failure($request, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure($request, 'We could not complete single sign-on right now. Please try again.');
        }

        if (! $user) {
            return $this->failure($request, 'Your verified account is not active or authorized for EduTrain.');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::guard('web')->login($user);
        $request->session()->put('auth_source', 'sso');

        return redirect()->route('dashboard');
    }

    private function failure(Request $request, string $message): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $message);
    }
}
