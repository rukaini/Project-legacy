<?php

namespace App\Http\Controllers\ExternalObserver;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EXTProfile extends Controller
{
    public function index()
    {
        $teacher = Teacher::with([
            'externalObserver'
        ])->findOrFail(
            Auth::guard('teacher')->id()
        );

        return view(
            'external.profile',
            compact('teacher')
        );
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[^A-Za-z0-9]/',
                'confirmed',
            ],
        ]);

        $teacher = Teacher::findOrFail(
            Auth::guard('teacher')->id()
        );

        $teacher->password = Hash::make(
            $request->password
        );

        $teacher->save();

        return redirect()
            ->route('external.profile')
            ->with(
                'success',
                'Password updated successfully.'
            );
    }
}