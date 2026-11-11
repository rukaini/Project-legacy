<?php

namespace App\Http\Controllers;

use App\Services\AttendanceXlsxExport;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AttendanceExportController extends Controller
{
    public function export(string $sessionID): Response
    {
        abort_unless(!empty($_SESSION['staffID'] ?? $_SESSION['staff_id']), 403);
        $session = DB::table('course_session as cs')->join('course as c', 'cs.courseID', '=', 'c.courseID')
            ->where('cs.sessionID', $sessionID)
            ->select('cs.*','c.courseName')->first();
        abort_unless($session, 404);
        $people = DB::table('course_participant as cp')->where('cp.courseID', $session->courseID)->get();
        $sources = [
            'teacher' => ['attendance','teacher_id','teacherID'],
            'staff' => ['attendance_staff','staffID','staffID'],
            'new_teacher' => ['attendance_guru_baru','gn_id','gn_id'],
            'public' => ['attendance_outsider','outsider_id','outsider_id'],
        ];
        $schools = DB::table('school')->pluck('schoolName','schoolID')->all();
        $newTeacherSchools = DB::table('guru_new')->whereIn('gn_id', $people->pluck('gn_id')->filter()->all())
            ->pluck('schoolID','gn_id')->all();
        $teacherSchools = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('v_teacher_current_school')) {
            $teacherSchools = DB::table('v_teacher_current_school')
                ->whereIn('teacherID', $people->pluck('teacherID')->filter()->all())
                ->pluck('schoolID','teacherID')->all();
        }
        $recordMap = [];
        foreach ($sources as $type => [$table, $column]) {
            $records = DB::table($table)->where('session_id', $sessionID)->get();
            foreach ($records as $r) {
                $id = (string) ($r->{$column} ?? '');
                $recordMap[$type.'|'.$id] = $r;
            }
        }
        $rows = [];
        foreach ($people as $person) {
            $type = strtolower((string)$person->participantType);
            if ($type === 'outsider') $type = 'public';
            if (!isset($sources[$type])) continue;
            $idColumn = $sources[$type][2];
            $id = (string) ($person->{$idColumn} ?? '');
            $attendance = $recordMap[$type.'|'.$id] ?? null;
            if (!$attendance || empty($attendance->scanned_at)) continue;
            $organization = trim((string) ($person->organisationName ?? ''));
            if ($organization === '' && $type === 'new_teacher') {
                $organization = $schools[$newTeacherSchools[$id] ?? ''] ?? '';
            } elseif ($organization === '' && $type === 'teacher') {
                $organization = $schools[$teacherSchools[$id] ?? ''] ?? '';
            }
            if ($organization === '') $organization = $type === 'staff' ? 'Al Amin EDU' : '-';
            $rows[] = [
                'scanned_at' => date('d/m/Y h:i A', strtotime($attendance->scanned_at)),
                'sortable_time' => (string)$attendance->scanned_at,
                'organization' => $organization,
                'name' => (string)$person->participantName,
            ];
        }
        usort($rows, fn($a, $b) => strcmp($a['sortable_time'], $b['sortable_time']));
        $details = [
            'title' => $session->courseName.' — '.$session->sessionName,
            'time' => date('d/m/Y', strtotime($session->sessionDate)).' · '.substr($session->startTime,0,5).'–'.substr($session->endTime,0,5),
            'location' => $session->location ?: '-',
        ];
        $filename = 'Kehadiran_'.preg_replace('/[^A-Za-z0-9_-]/','_', $sessionID).'.xlsx';
        return response()->streamDownload(function () use ($details, $rows) {
            $temp = tempnam(sys_get_temp_dir(), 'trainhub_xlsx_');
            try {
                AttendanceXlsxExport::write($temp, $details, $rows);
                readfile($temp);
            } finally {
                if (is_file($temp)) unlink($temp);
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
