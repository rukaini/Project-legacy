<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * End only this system's session, then return to Al Amin Login.
     * The redirect target comes from config, never from the request.
     */
    public function destroy(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $portal = config('gateway.portal_url');

        return Inertia::location(
            is_string($portal) && filter_var($portal, FILTER_VALIDATE_URL) ? $portal : route('login')
        );
    }
}
