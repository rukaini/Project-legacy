<?php

namespace App\Http\Controllers;

use App\Services\AuthenticatedPasswordChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ChangePasswordController extends Controller
{
    public function edit(): Response
    {
        return $this->noStore(response()->view('auth.change-password'));
    }

    public function update(Request $request, AuthenticatedPasswordChangeService $passwords): RedirectResponse
    {
        $validator = Validator::make($request->only([
            'current_password',
            'password',
            'password_confirmation',
        ]), [
            'current_password' => ['required', 'string', 'max:1024'],
            'password' => ['required', 'string', 'min:12', 'max:1024', 'confirmed', 'different:current_password'],
        ], [
            'password.different' => 'The new password must be different from your current password.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $identity = $request->session()->get('gateway.identity');
        if (! is_array($identity)) {
            return redirect()->route('login');
        }

        try {
            $passwords->change(
                $identity,
                (string) $request->input('current_password'),
                (string) $request->input('password'),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'password' => 'We could not change your password safely. No passwords were changed. Please try again.',
            ]);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'status',
            'Your password has been changed. Sign in with your new password.',
        );
    }

    private function noStore(Response $response): Response
    {
        return $response
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
