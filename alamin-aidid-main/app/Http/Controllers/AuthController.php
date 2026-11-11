<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin(): RedirectResponse
    {
        return redirect()->away($this->gatewayUrl('url'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away($this->gatewayUrl('portal_url'));
    }

    private function gatewayUrl(string $key): string
    {
        $url = (string) config("gateway.{$key}");
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        abort_unless(
            filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true),
            500,
            'Al Amin Login is not configured for this system.',
        );

        return $url;
    }
}
