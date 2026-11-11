<?php

namespace App\Http\Controllers;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class OutsiderRegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        return $request->session()->has('gateway.identity')
            ? redirect()->route('systems.index')
            : view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $exists = DB::table('users')
                        ->whereRaw('LOWER(TRIM(`email`)) = ?', [(string) $value])
                        ->exists();

                    if ($exists) {
                        $fail('An account with this email address already exists.');
                    }
                },
            ],
            'password' => ['required', 'string', 'max:1024', 'confirmed', Password::min(8)],
        ]);

        try {
            DB::transaction(function () use ($validated): void {
                $now = now();

                try {
                    $userId = DB::table('users')->insertGetId([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } catch (UniqueConstraintViolationException $exception) {
                    throw ValidationException::withMessages([
                        'email' => 'An account with this email address already exists.',
                    ]);
                }

                DB::table('outsider')->insert([
                    'name' => $validated['name'],
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $roles = DB::table('roles')
                    ->where('name', 'outsider')
                    ->where('guard_name', 'web')
                    ->limit(2)
                    ->get();

                if ($roles->count() !== 1) {
                    throw new RuntimeException('The canonical Outsider role is not uniquely configured.');
                }

                DB::table('model_has_roles')->insert([
                    'role_id' => $roles->first()->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['registration' => 'We could not create your account. Please try again later.'])
                ->onlyInput('name', 'email');
        }

        return redirect()->route('login')->with('status', 'Account created successfully. You can now sign in.');
    }
}
