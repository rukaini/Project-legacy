<?php

namespace App\Http\Controllers;

use App\Support\TrainHubSettings;
use App\Services\NewTeacherLmsRequirementService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DashboardRulesController extends Controller
{
    public function index(): Response
    {
        $this->activeStaff();
        // With no database trigger, backfill recent appointments at the current target.
        NewTeacherLmsRequirementService::snapshotMissingFromCurrentDefault();
        return response()->view('settings.credit-hours', [
            'rules' => TrainHubSettings::all(),
            'snapshotReady' => NewTeacherLmsRequirementService::ready(),
        ]);
    }

    private function activeStaff(): string
    {
        $staffID = (string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? '');
        abort_if($staffID === '', 403);
        abort_unless(DB::table('staff_edu')->where('staffID', $staffID)
            ->whereRaw("LOWER(TRIM(COALESCE(status, 'active'))) = 'active'")->exists(), 403);
        return $staffID;
    }

    public function update(Request $request): Response
    {
        $staffID = $this->activeStaff();
        $values = $request->validate([
            'new_teacher_lms_required' => ['required','integer','min:1','max:100'],
            'training_target_hours' => ['required','numeric','min:0','max:40'],
            'tarbiah_target_hours' => ['required','numeric','min:0','max:40'],
            'ladap_target_hours' => ['required','numeric','min:0','max:500'],
            'trainer_multiplier' => ['required','numeric','min:0','max:5'],
            'alamin_multiplier' => ['required','numeric','min:0','max:5'],
            'other_multiplier' => ['required','numeric','min:0','max:5'],
            'outside_order_multiplier' => ['required','numeric','min:0','max:5'],
        ]);
        if ((float)$values['training_target_hours'] + (float)$values['tarbiah_target_hours'] > 40.0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'training_target_hours' => 'Staff EDU annual Training + Tarbiah goals cannot exceed 40 hours.',
            ]);
        }
        if (!NewTeacherLmsRequirementService::ready()) {
            throw ValidationException::withMessages([
                'new_teacher_lms_required' => 'The New Teacher LMS requirement table is missing.',
            ]);
        }
        // Freeze each existing teacher's requirement BEFORE changing the global default.
        DB::transaction(function () use ($values, $staffID): void {
            // Serialize LMS default changes made through TrainHub, then record
            // any teachers added by other systems while the OLD default applies.
            $oldValue = DB::table('trainhub_settings')
                ->where('setting_key', 'new_teacher_lms_required')
                ->lockForUpdate()
                ->value('setting_value');
            $oldRequirement = is_numeric($oldValue)
                ? (int) $oldValue
                : (int) TrainHubSettings::DEFAULTS['new_teacher_lms_required'];

            NewTeacherLmsRequirementService::snapshotExisting($oldRequirement);
            TrainHubSettings::set($values, $staffID);
        });
        DB::table('audit_log')->insert([
            'staffID' => $staffID, 'userName' => (string) ($_SESSION['staffName'] ?? $staffID),
            'actionType' => 'UPDATE', 'tableName' => 'trainhub_settings',
            'newValue' => 'Updated credit multipliers and annual goals; existing LMS snapshots retained',
        ]);
        return redirect()->route('credit.settings')->with('success', 'Training requirements and credit rules updated.');
    }
}
