<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrincipalAuthenticate
{
   public function handle(Request $request, Closure $next)
{
    if ($request->is('login')) return $next($request);

    // Use strtolower to make it case-insensitive
    $role = strtolower(session('role'));

    if (!session()->has('user') || $role !== 'principal') {
        return redirect()->route('login');
    }

    return $next($request);
}
}