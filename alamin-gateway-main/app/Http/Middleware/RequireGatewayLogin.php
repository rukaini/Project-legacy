<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireGatewayLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_array($request->session()->get('gateway.identity'))) {
            return redirect()->route('login')->with('status', 'Please sign in to continue.');
        }

        return $next($request);
    }
}
