<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StaffAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        // Debug logging
        Log::info('StaffAuthenticate - Checking session', [
            'url' => $request->fullUrl(),
            'has_user' => session()->has('user'),
            'role' => session('role'),
            'user_id' => session('userID'),
            'session_id' => session()->getId()
        ]);

        // Check if user exists in session
        if (!session()->has('user')) {
            Log::warning('StaffAuthenticate - No user in session');
            return redirect()->route('login')
                ->withErrors(['email' => 'Sila log masuk untuk meneruskan.']);
        }

        // Check if user has staff role
        if (session('role') != 'staff') {
            Log::warning('StaffAuthenticate - User is not staff', [
                'role' => session('role')
            ]);
            return redirect()->route('login')
                ->withErrors(['email' => 'Akses ditolak. Kakitangan sahaja.']);
        }

        Log::info('StaffAuthenticate - Access granted', [
            'user_id' => session('userID'),
            'role' => session('role')
        ]);

        return $next($request);
    }
}