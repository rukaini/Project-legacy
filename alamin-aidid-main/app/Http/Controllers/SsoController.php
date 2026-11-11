<?php

namespace App\Http\Controllers;

use App\Exceptions\SsoException;
use App\Services\AididSsoAuthenticator;
use App\Services\GatewaySsoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SsoController extends Controller
{
    public function callback(
        Request $request,
        GatewaySsoClient $gateway,
        AididSsoAuthenticator $authenticator,
    ): RedirectResponse {
        $code = (string) $request->query('code', '');
        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return $this->failure($request);
        }

        try {
            $identity = $gateway->redeem($code);
            $local = $authenticator->resolve($identity);
        } catch (SsoException) {
            return $this->failure($request);
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure($request);
        }

        if (! $local) {
            return $this->failure($request);
        }

        $dashboard = (string) $local['dashboard'];
        unset($local['dashboard']);
        $local['auth_source'] = 'sso';

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put($local);

        return redirect()->route($dashboard);
    }

    private function failure(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $portalUrl = (string) config('gateway.url');
        $scheme = strtolower((string) parse_url($portalUrl, PHP_URL_SCHEME));

        abort_unless(
            filter_var($portalUrl, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true),
            503,
            'Al Amin Login is not configured for this system.',
        );

        return redirect()->away($portalUrl);
    }
}
