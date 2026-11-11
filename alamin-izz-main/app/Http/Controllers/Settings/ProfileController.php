<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\Contracts\TeacherRepositoryInterface;
use App\Models\Outsider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private TeacherRepositoryInterface   $teachers,
        private PrincipalRepositoryInterface $principals,
    ) {}

    public function edit(Request $request): Response
    {
        $user    = $request->user();
        $profile = [];

        if ($user->isTeacher()) {
            $dto     = $this->teachers->findByEmail($user->email);
            $profile = [
                'teacherName'   => $dto?->name,
                'phoneNumber'   => $dto?->phoneNumber,
                'ICNumber'      => $dto?->ICNumber,
                'gender'        => $dto?->gender,
                'address'       => $dto?->address,
                'race'          => $dto?->race,
                'martialStatus' => $dto?->martialStatus,
                'appointedDate' => $dto?->appointedDate?->toDateString(),
                'serviceDate'   => $dto?->serviceDate?->toDateString(),
                'pensionDate'   => $dto?->pensionDate?->toDateString(),
                'latestAge'     => $dto?->latestAge,
                'schoolName'    => $dto?->schoolName,
            ];
        } elseif ($user->isGuruBesar()) {
            $dto     = $this->principals->findByEmail($user->email);
            $profile = [
                'principalName' => $dto?->name,
                'phoneNumber'   => $dto?->phoneNumber,
                'ICNumber'      => $dto?->ICNumber,
                'gender'        => $dto?->gender,
                'address'       => $dto?->address,
                'race'          => $dto?->race,
                'martialStatus' => $dto?->martialStatus,
                'appointedDate' => $dto?->appointedDate?->toDateString(),
                'schoolName'    => $dto?->schoolName,
            ];
        } elseif ($user->isOutsider()) {
            $outsider = Outsider::where('user_id', $user->id)->first();
            $profile  = [
                'organization' => $outsider?->organization,
                'phoneNumber'  => $outsider?->phoneNumber,
                'ICNumber'     => $outsider?->ICNumber,
                'gender'       => $outsider?->gender,
            ];
        } elseif ($user->isGuruBaru() || $user->isHR()) {
            // No extended profile table — name/email only (handled via User model)
            $profile = [];
        }

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status'          => session('status'),
            'profile'         => $profile,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user      = $request->user();

        if ($user->isTeacher()) {
            $this->teachers->updateProfile($user->email, array_filter([
                'phoneNumber' => $validated['phone_number'] ?? null,
                'address'     => $validated['address']      ?? null,
            ], fn ($v) => $v !== null));
        } elseif ($user->isGuruBesar()) {
            $this->principals->updateProfile($user->email, array_filter([
                'phoneNumber' => $validated['phone_number'] ?? null,
                'address'     => $validated['address']      ?? null,
            ], fn ($v) => $v !== null));
        } elseif ($user->isOutsider()) {
            $user->fill([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            Outsider::where('user_id', $user->id)->update([
                'organization' => $validated['organization'] ?? null,
                'phoneNumber'  => $validated['phone_number_outsider'] ?? null,
                'ICNumber'     => $validated['ic_number'] ?? null,
                'gender'       => $validated['gender'] ?? null,
            ]);
        } elseif ($user->isGuruBaru() || $user->isHR()) {
            $user->fill([
                'name'  => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();
        }

        return to_route('profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
