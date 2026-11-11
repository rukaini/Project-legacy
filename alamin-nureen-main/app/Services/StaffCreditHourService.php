<?php

namespace App\Services;

use App\Support\TrainHubSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Approved Training is calculated here; externally verified Tarbiah uses tarbiah.credit_hours verbatim. */
final class StaffCreditHourService
{
    private static function duration(?string $start, ?string $end): float
    {
        if (!$start || !$end) return 0.0;
        $s = strtotime('2000-01-01 '.$start);
        $e = strtotime('2000-01-01 '.$end);
        return ($s !== false && $e !== false && $e > $s) ? ($e - $s) / 3600 : 0.0;
    }

    public static function totals(string $staffID, int $year, ?array $settings = null): array
    {
        $settings ??= TrainHubSettings::all();
        $training = 0.0;
        $tarbiah = 0.0;
        $records = [];
        $firstDay = sprintf('%04d-01-01', $year);
        $nextYear = sprintf('%04d-01-01', $year + 1);

        $staffIC = preg_replace('/[^0-9]/', '', (string) (DB::table('staff_edu')->where('staffID', $staffID)->value('ICNumber') ?? ''));
        $trainingRows = DB::table('attendance_staff as ast')
            ->join('course_session as cs', 'ast.session_id', '=', 'cs.sessionID')
            ->join('course as c', 'cs.courseID', '=', 'c.courseID')
            ->where('ast.staffID', $staffID)
            ->where('ast.attendance_status', 'approved')
            ->where('cs.sessionDate', '>=', $firstDay)
            ->where('cs.sessionDate', '<', $nextYear)
            ->select('cs.courseID', 'c.courseName', 'c.organiserName', 'cs.sessionID', 'cs.sessionDate', 'cs.startTime', 'cs.endTime', 'ast.hours_ladap')
            ->addSelect(Schema::hasColumn('course', 'credit_hours') ? 'c.credit_hours' : DB::raw('NULL AS credit_hours'))
            ->get();
        $courses = $trainingRows->groupBy('courseID');
        $durationTotals = [];
        $trainedCourses = [];
        if ($courses->isNotEmpty()) {
            $sessions = DB::table('course_session')->whereIn('courseID', $courses->keys()->all())
                ->get(['courseID', 'startTime', 'endTime']);
            foreach ($sessions as $session) {
                $durationTotals[$session->courseID] = ($durationTotals[$session->courseID] ?? 0) + self::duration($session->startTime, $session->endTime);
            }
            if ($staffIC !== '') {
                $trainers = DB::table('trainer as tr')
                    ->join('session_trainer as st', 'tr.trainerID', '=', 'st.trainerID')
                    ->join('course_session as cs', 'st.sessionID', '=', 'cs.sessionID')
                    ->whereIn('cs.courseID', $courses->keys()->all())
                    ->whereRaw("REPLACE(REPLACE(TRIM(tr.trainerIC), '-', ''), ' ', '') = ?", [$staffIC])
                    ->distinct()->pluck('cs.courseID');
                $trainedCourses = array_fill_keys($trainers->all(), true);
            }
        }
        $approvedOutsideOrders = Schema::hasTable('trainhub_staff_training_orders')
            ? DB::table('trainhub_staff_training_orders')
                ->where('staffID', $staffID)->where('decision', 'approved')
                ->pluck('courseID')->flip()->all()
            : [];
        foreach ($courses as $courseID => $sessions) {
            $first = $sessions->first();
            $hours = 0.0;
            foreach ($sessions as $row) {
                $hours += self::duration($row->startTime, $row->endTime)
                    ?: max(0, (float) $row->hours_ladap);
            }
            $configuredHours = (float) ($first->credit_hours ?? 0);
            $scheduledDuration = (float) ($durationTotals[$courseID] ?? 0);
            $base = $first->credit_hours !== null && $scheduledDuration > 0
                ? $configuredHours * min(1, $hours / $scheduledDuration) : $hours;
            if (isset($trainedCourses[$courseID])) {
                $multiplier = $settings['trainer_multiplier'];
                $rule = 'Trainer';
            } elseif (stripos((string) $first->organiserName, 'Al Amin Edu Oasis') !== false) {
                $multiplier = $settings['alamin_multiplier'];
                $rule = 'Al Amin organiser';
            } elseif (isset($approvedOutsideOrders[$courseID])) {
                $multiplier = (float) $settings['outside_order_multiplier'];
                $rule = 'Other organiser (approved management order)';
            } else {
                $multiplier = (float) $settings['other_multiplier'];
                $rule = 'Other organiser (voluntary / unapproved order)';
            }
            $credited = round($base * $multiplier, 2);
            $training += $credited;
            $records[] = ['category' => 'Training', 'name' => $first->courseName,
                'date' => $first->sessionDate, 'hours' => $credited, 'rule' => $rule.' ×'.$multiplier];
        }
        if (Schema::hasTable('staff_tarbiah_attendance')) {
            $tarbiahRows = DB::table('staff_tarbiah_attendance as sta')
                ->join('tarbiah as t', 'sta.tarbiah_id', '=', 't.tarbiah_id')
                ->where('sta.staffID', $staffID)
                ->where('sta.attendance_status', 'approved')
                ->where('t.session_date', '>=', $firstDay)
                ->where('t.session_date', '<', $nextYear)
                ->select('t.title', 't.session_date')
                ->addSelect(Schema::hasColumn('tarbiah', 'credit_hours') ? 't.credit_hours' : DB::raw('NULL AS credit_hours'))
                ->get();
            foreach ($tarbiahRows as $row) {
                // Tarbiah credit is provided by the external Tarbiah table, never recalculated from session duration.
                $hours = $row->credit_hours !== null ? (float) $row->credit_hours : 0.0;
                $hours = round(max(0, $hours), 2);
                $tarbiah += $hours;
                $records[] = ['category' => 'Tarbiah', 'name' => $row->title,
                    'date' => $row->session_date, 'hours' => $hours, 'rule' => 'Tarbiah credit_hours (external attendance approved)'];
            }
        }
        $trainingAllowed = min($training, (float) $settings['training_target_hours']);
        $tarbiahAllowed = min($tarbiah, (float) $settings['tarbiah_target_hours']);
        return [
            'raw_training' => round($training, 2), 'raw_tarbiah' => round($tarbiah, 2),
            'training' => round($trainingAllowed, 2), 'tarbiah' => round($tarbiahAllowed, 2),
            'total' => round($trainingAllowed + $tarbiahAllowed, 2),
            'target' => (float) $settings['training_target_hours'] + (float) $settings['tarbiah_target_hours'],
            'records' => $records,
        ];
    }

    /** Keep the old staff_edu credit_hour cache in sync with the configurable goals. */
    public static function refreshCache(string $staffID): void
    {
        $calculated = self::totals($staffID, (int) date('Y'));
        // The older DB has credit_hour decimal(5,2) CHECK (0 <= value <= 40).
        // Schema patch widens that CHECK, but guard older DBs too.
        $credit = round(min(40.0, $calculated['total']), 2);
        $current = DB::table('staff_edu')->where('staffID', $staffID)->value('credit_hour');
        // Do not write a value above 40 while a legacy chk_staff_edu_1 rule is active.
        // The setup SQL replaces that rule with a nonnegative range up to 999.99.
        if ($current !== null && abs((float) $current - $credit) >= 0.005) {
            DB::table('staff_edu')->where('staffID', $staffID)->update(['credit_hour' => $credit]);
        }
    }
}
