<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HRAdministrator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class HRProfileController extends Controller
{
    public function index()
    {
        $hr = Auth::guard('hr')->user();

        return view('hr.profile', compact('hr'));
    }

    public function updatePassword(Request $request)
    {
        $hr = HRAdministrator::findOrFail(
            Auth::guard('hr')->id()
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

        $hr->password = Hash::make(
            $request->password
        );

        $hr->password_change_required = 0;

        $hr->save();

        return redirect()
            ->route('hr.profile')
            ->with(
                'success',
                'Password updated successfully.'
            );
    }
}