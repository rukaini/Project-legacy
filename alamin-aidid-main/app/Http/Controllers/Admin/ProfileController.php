<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CanonicalTrainingQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function profile()
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $staffId = session('user_id');
        $staff = CanonicalTrainingQuery::staff()->where('staff_edu.staffID', $staffId)->first();
        if (! $staff) {
            return redirect()->route('login')->with('error', 'Admin account not found.');
        }

        $proposalsReviewed = 0;
        $proposalsApproved = 0;
        $proposalsRejected = 0;
        if (Schema::hasTable('course_proposal')
            && Schema::hasColumn('course_proposal', 'reviewed_by')) {
            $reviewedRows = DB::table('course_proposal')
                ->where('reviewed_by', $staffId)
                ->whereIn('status', ['Approved', 'Rejected'])
                ->select('status')
                ->get();
            $proposalsReviewed = $reviewedRows->count();
            $proposalsApproved = $reviewedRows->where('status', 'Approved')->count();
            $proposalsRejected = $reviewedRows->where('status', 'Rejected')->count();
        }

        $recentReviews = collect();
        if (Schema::hasTable('course_proposal')
            && Schema::hasColumn('course_proposal', 'reviewed_by')) {
            $recentReviews = DB::table('course_proposal')
                ->leftJoin('trainer', 'course_proposal.trainer_id', '=', 'trainer.trainerID')
                ->where('course_proposal.reviewed_by', $staffId)
                ->whereNotNull('course_proposal.reviewed_at')
                ->whereIn('course_proposal.status', ['Approved', 'Rejected'])
                ->select(
                    'course_proposal.proposal_id',
                    'course_proposal.proposed_title',
                    'course_proposal.status',
                    'course_proposal.reviewed_at',
                    'trainer.trainerName as trainer_name'
                )
                ->orderByDesc('course_proposal.reviewed_at')
                ->orderByDesc('course_proposal.proposal_id')
                ->limit(10)
                ->get();
        }

        $approvalRate = $proposalsReviewed > 0
            ? (int) round($proposalsApproved / $proposalsReviewed * 100)
            : null;
        $daysToRetirement = null;
        if (! empty($staff->retirement_date)) {
            try {
                $retirementDate = Carbon::parse($staff->retirement_date)->startOfDay();
                $today = Carbon::now()->startOfDay();
                if ($retirementDate->gte($today)) {
                    $daysToRetirement = (int) $today->diffInDays($retirementDate);
                }
            } catch (\Throwable $e) {
            }
        }

        $staff->service_duration = null;
        if (! empty($staff->hire_date)) {
            try {
                $hireDate = Carbon::parse($staff->hire_date);
                $now = Carbon::now();
                if ($hireDate->lte($now)) {
                    $diff = $hireDate->diff($now);
                    $years = (int) $diff->y;
                    $months = (int) $diff->m;
                    if ($years === 0 && $months === 0) {
                        $staff->service_duration = $diff->d.' days';
                    } elseif ($years === 0) {
                        $staff->service_duration = $months.' month'.($months === 1 ? '' : 's');
                    } else {
                        $staff->service_duration = $years.' year'.($years === 1 ? '' : 's')
                            .($months > 0 ? ', '.$months.' month'.($months === 1 ? '' : 's') : '');
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $staff->current_age = $staff->latestAge;

        return view('admin.profile', [
            'staff' => $staff,
            'proposalsReviewed' => $proposalsReviewed,
            'proposalsApproved' => $proposalsApproved,
            'proposalsRejected' => $proposalsRejected,
            'approvalRate' => $approvalRate,
            'daysToRetirement' => $daysToRetirement,
            'recentReviews' => $recentReviews,
        ]);
    }

    public function updatePassword(Request $request)
    {
        if (session('user_role') !== 'admin') {
            return redirect()->route('login')->with('error', 'Please login as admin first.');
        }

        $staffId = session('user_id');

        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'new_password.confirmed' => 'The new password confirmation does not match.',
            'new_password.different' => 'Your new password must be different from your current one.',
            'new_password.min' => 'The new password must be at least 8 characters.',
        ]);

        $staff = DB::table('staff_edu')
            ->where('staffID', $staffId)
            ->first(['staffID', 'password']);
        if (! $staff) {
            return redirect()->route('login')->with('error', 'Admin account not found.');
        }

        $stored = (string) $staff->password;
        $current = $request->input('current_password');
        $passwordOk = Str::startsWith($stored, ['$2y$', '$2a$', '$2b$'])
            ? Hash::check($current, $stored)
            : hash_equals($stored, $current);

        if (! $passwordOk) {
            return redirect()->route('admin.profile', ['tab' => 'security'])
                ->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        DB::table('staff_edu')->where('staffID', $staffId)->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return redirect()->route('admin.profile', ['tab' => 'security'])
            ->with('success', 'Password changed successfully.');
    }

    public function updatePicture(Request $request)
    {
        return redirect()->route('admin.profile')
            ->with('error', 'Staff profile pictures are managed by the Staff EDU owner.');
    }

    public function deletePicture(Request $request)
    {
        return redirect()->route('admin.profile')
            ->with('error', 'Staff profile pictures are managed by the Staff EDU owner.');
    }
}
