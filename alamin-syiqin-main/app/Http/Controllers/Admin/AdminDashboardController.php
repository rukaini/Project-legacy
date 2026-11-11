<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();

        abort_if(!$admin, 403, 'Unauthorized access.');

        // Count PRE form versions
        $preFormCount = DB::table('pre_form')
            ->count();

        // Count PDPC form versions
        $pdpcFormCount = DB::table('pdpc_form')
            ->count();

        // Count Feedback form versions
        $postFormCount = DB::table('post_form')
            ->count();

        // Calculate total form versions
        $totalForms =
            $preFormCount
            + $pdpcFormCount
            + $postFormCount;

        // Get active PRE form
        $activePreForm = DB::table('pre_form')
            ->where('status', 'Active')
            ->orderByDesc('formID')
            ->first();

        // Get active PDPC form
        $activePdpcForm = DB::table('pdpc_form')
            ->where('status', 'Active')
            ->orderByDesc('formID')
            ->first();

        // Get active Feedback form
        $activePostForm = DB::table('post_form')
            ->where('status', 'Active')
            ->orderByDesc('formID')
            ->first();

        // Calculate total active forms
        $activeForms =
            ($activePreForm ? 1 : 0)
            + ($activePdpcForm ? 1 : 0)
            + ($activePostForm ? 1 : 0);

        // Count all observation audit records
        $totalRecords = DB::table('audit_observation')
            ->count();

        // Get latest five observation audit records
        $recentAudits = DB::table('audit_observation')
            ->leftJoin(
                'teacher',
                'audit_observation.teacherID',
                '=',
                'teacher.teacherID'
            )
            ->leftJoin(
                'guru_new',
                'audit_observation.gn_id',
                '=',
                'guru_new.gn_id'
            )
            ->select(
                'audit_observation.*',
                'teacher.teacherName',
                'guru_new.gn_name'
            )
            ->orderByDesc('audit_observation.audit_date')
            ->orderByDesc('audit_observation.audit_time')
            ->take(5)
            ->get();

        // Return admin dashboard
        return view(
            'admin.dashboard',
            compact(
                'admin',
                'totalForms',
                'activeForms',
                'totalRecords',
                'preFormCount',
                'pdpcFormCount',
                'postFormCount',
                'activePreForm',
                'activePdpcForm',
                'activePostForm',
                'recentAudits'
            )
        );
    }
}