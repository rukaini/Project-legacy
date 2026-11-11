<?php

namespace App\Http\Controllers\Observer;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OBProfile extends Controller
{
    public function index()
    {
        $teacher = Teacher::with([
            'observer'
        ])->findOrFail(
            Auth::guard('teacher')->id()
        );

        return view(
            'observer.profile',
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
            ->route('observer.profile')
            ->with(
                'success',
                'Password updated successfully.'
            );
    }
}