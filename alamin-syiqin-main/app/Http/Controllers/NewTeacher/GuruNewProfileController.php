<?php

namespace App\Http\Controllers\NewTeacher;

use App\Http\Controllers\Controller;
use App\Models\GuruNew;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class GuruNewProfileController extends Controller
{
    public function edit()
    {
        $guru = GuruNew::with('school')
            ->findOrFail(Auth::guard('new_teacher')->id());

        $addressLine = '';
        $postcode = '';
        $city = '';
        $state = '';

        if ($guru->address) {

            $addressParts = explode(', ', $guru->address);
            $totalParts = count($addressParts);

            if ($totalParts >= 4) {

                $state = $addressParts[$totalParts - 1];

                $city = $addressParts[$totalParts - 2];

                $postcode = $addressParts[$totalParts - 3];

                $addressLine = implode(
                    ', ',
                    array_slice(
                        $addressParts,
                        0,
                        $totalParts - 3
                    )
                );
            }
        }

        return view(
            'newteacher.profile',
            compact(
                'guru',
                'addressLine',
                'postcode',
                'city',
                'state'
            )
        );
    }


    public function update(Request $request)
    {
        $guru = GuruNew::findOrFail(
            Auth::guard('new_teacher')->id()
        );

        $request->validate([

            'email' => [
                'required',
                'email',
                'max:100',

                Rule::unique('guru_new', 'email')
                    ->ignore($guru->gn_id, 'gn_id'),
            ],

            'phone_number' => [
                'required',
                'string',
                'max:20',
            ],

            'marital_status' => [
                'nullable',
                'in:Single,Married,Divorced',
            ],

            'race' => [
                'nullable',
                'in:Malay,Chinese,Indian,Others',
            ],

            'other_race' => [
                'nullable',
                'required_if:race,Others',
                'string',
                'max:50',
            ],

            'address_line' => [
                'required',
                'string',
                'max:150',
            ],

            'postcode' => [
                'required',
                'digits:5',
            ],

            'city' => [
                'required',
                'string',
                'max:50',
            ],

            'state' => [
                'required',
                'string',
                'max:50',
            ],
        ]);

        $guru->email = $request->email;

        $guru->phone_number = $request->phone_number;

        $guru->marital_status = $request->marital_status;

        $guru->race = $request->race === 'Others'
            ? $request->other_race
            : $request->race;

        $guru->address =
            $request->address_line . ', ' .
            $request->postcode . ', ' .
            $request->city . ', ' .
            $request->state;

        $guru->save();

        return redirect()
            ->route('new_teacher.profile')
            ->with('success', 'Profile updated successfully');
    }

    public function updatePassword(Request $request)
    {
        $guru = GuruNew::findOrFail(
            Auth::guard('new_teacher')->id()
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

        $guru->password = Hash::make(
            $request->password
        );

        $guru->save();

        return redirect()
            ->route('new_teacher.profile')
            ->with(
                'success',
                'Password updated successfully'
            );
    }
}
