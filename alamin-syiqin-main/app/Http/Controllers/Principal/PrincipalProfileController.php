<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\Principal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PrincipalProfileController extends Controller
{
    public function index()
    {
        $principal = Principal::with('school')
            ->findOrFail(
                Auth::guard('principal')->id()
            );

        return view(
            'principal.profile',
            compact('principal')
        );
    }

    public function updatePassword(Request $request)
    {
        $principal = Principal::findOrFail(
            Auth::guard('principal')->id()
        );

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


        $principal->password = Hash::make(
            $request->password
        );

        $principal->save();


        return redirect()
            ->route('principal.profile')
            ->with(
                'success',
                'Password updated successfully.'
            );
    }
}