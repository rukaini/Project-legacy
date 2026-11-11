<?php

namespace App\Http\Controllers;

use App\Services\AdministratorPasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AdministratorPasswordResetController extends Controller
{
    public function edit(string $token, AdministratorPasswordResetService $resets): Response
    {
        abort_unless($resets->valid($token), 410, 'This password reset link is invalid or has expired.');

        return $this->noStore(response()->view('auth.administrator-reset-password', compact('token')));
    }

    public function update(
        Request $request,
        string $token,
        AdministratorPasswordResetService $resets,
    ): RedirectResponse|Response {
        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Password::min(12), 'max:1024'],
        ]);

        try {
            if (! $resets->consume($token, $validated['password'])) {
                return $this->invalidLinkResponse();
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->noStore(response()->view('auth.administrator-reset-password', [
                'token' => $token,
                'resetError' => 'We could not reset the Administrator password right now. No credentials were changed. Please try again.',
            ], 503));
        }

        return redirect()->route('login')->with(
            'status',
            'Your Administrator password has been reset. You can now sign in to ELIT Portal.',
        );
    }

    private function invalidLinkResponse(): Response
    {
        return $this->noStore(response()->view('auth.administrator-reset-invalid', status: 410));
    }

    private function noStore(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
