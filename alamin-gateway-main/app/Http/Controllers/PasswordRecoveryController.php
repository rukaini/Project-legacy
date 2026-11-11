<?php

namespace App\Http\Controllers;

use App\Mail\AdministratorPasswordResetMail;
use App\Services\AdministratorAccountService;
use App\Services\AdministratorPasswordResetService;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PasswordRecoveryController extends Controller
{
    public function create(): Response
    {
        return $this->noStore(response()->view('auth.forgot-password'));
    }

    public function store(
        Request $request,
        PasswordRecoveryService $recovery,
        AdministratorPasswordResetService $administratorResets,
        AdministratorAccountService $administrator,
    ): Response {
        $request->merge(['identifier' => trim((string) $request->input('identifier'))]);
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $recovery->recover($validated['identifier']);
        } catch (Throwable $exception) {
            return $this->errorResponse(
                $validated['identifier'],
                'We could not recover your account right now. Please try again later.',
            );
        }

        if ($result['status'] === 'success') {
            return $this->noStore(response()->view('auth.recovered-password', [
                'password' => $result['password'],
            ]));
        }

        if ($result['status'] === 'administrator_ic') {
            return $this->errorResponse(
                $validated['identifier'],
                'For this account, enter the registered email address to continue with secure recovery.',
            );
        }

        if ($result['status'] === 'administrator_email') {
            $token = null;
            try {
                $token = $administratorResets->issue();
                Mail::to($administrator->email())->send(new AdministratorPasswordResetMail(
                    $this->administratorResetUrl($token),
                ));
            } catch (Throwable $exception) {
                if (is_string($token)) {
                    $administratorResets->revoke($token);
                }
                report($exception);

                return $this->errorResponse(
                    $validated['identifier'],
                    'We could not send the secure reset email right now. No credentials were changed. Please try again later.',
                );
            }

            return $this->noStore(response()->view('auth.forgot-password', [
                'recoveryStatus' => 'A secure password reset link has been sent to the registered Administrator email address. The link expires in 15 minutes.',
            ]));
        }

        $message = $result['status'] === 'ambiguous'
            ? 'We found more than one account for this information. Please use your IC number if available or contact the administrator.'
            : 'We could not recover an active account with that information. Check it and try again, or contact the administrator.';

        return $this->errorResponse($validated['identifier'], $message);
    }

    private function errorResponse(string $identifier, string $message): Response
    {
        return $this->noStore(response()->view('auth.forgot-password', [
            'identifier' => $identifier,
            'recoveryError' => $message,
        ], 422));
    }

    private function administratorResetUrl(string $token): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $scheme = strtolower((string) parse_url($baseUrl, PHP_URL_SCHEME));
        if (! filter_var($baseUrl, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            throw new \RuntimeException('APP_URL must be a valid HTTP or HTTPS URL for Administrator recovery.');
        }

        return $baseUrl.route('password.admin.reset', ['token' => $token], false);
    }

    private function noStore(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
