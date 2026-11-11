<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GNListController extends Controller
{
    // Show new teachers from own school
    public function index(Request $request)
    {
        $principal = Auth::guard('principal')->user();
        $search = $request->search;

        // Get teachers
        $guruNew = DB::table('guru_new')
            ->join('school', 'guru_new.schoolID', '=', 'school.schoolID')
            ->where('guru_new.schoolID', $principal->schoolID)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('guru_new.gn_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('guru_new.email', 'LIKE', '%' . $search . '%')
                        ->orWhere('guru_new.phone_number', 'LIKE', '%' . $search . '%')
                        ->orWhere('guru_new.ic_number', 'LIKE', '%' . $search . '%');
                });
            })
            ->select(
                'guru_new.gn_id',
                'guru_new.ic_number',
                'guru_new.gn_name',
                'guru_new.phone_number',
                'guru_new.email',
                'guru_new.marital_status',
                'guru_new.gender',
                'guru_new.address',
                'guru_new.race',
                'guru_new.appointed_date',
                'guru_new.current_status',
                'school.schoolName'
            )
            ->orderBy('guru_new.gn_name')
            ->paginate(10)
            ->withQueryString();

        // Count teachers under principal school
        $totalTeachers = DB::table('guru_new')
            ->where('schoolID', $principal->schoolID)
            ->count();

        return view('principal.gn-list', compact('guruNew', 'totalTeachers'));
    }
}
