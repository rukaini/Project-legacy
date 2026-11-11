<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HrAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('user') || session('role') != 'hr') {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sila log masuk untuk meneruskan.']);
        }

        return $next($request);
    }
}