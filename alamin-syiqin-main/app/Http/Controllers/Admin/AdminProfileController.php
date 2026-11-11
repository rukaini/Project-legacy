<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffEdu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    public function index()
    {
        $admin = StaffEdu::findOrFail(
            Auth::guard('admin')->id()
        );

        return view('admin.profile', compact('admin'));
    }


    public function updatePassword(Request $request)
    {
        $admin = StaffEdu::findOrFail(
            Auth::guard('admin')->id()
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

        $admin->password = Hash::make(
            $request->password
        );


        $admin->save();

        return redirect()
            ->route('admin.profile')
            ->with(
                'success',
                'Password updated successfully.'
            );
    }
}