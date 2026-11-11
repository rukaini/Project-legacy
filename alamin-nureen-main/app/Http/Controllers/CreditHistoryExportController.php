<?php

namespace App\Http\Controllers;

use App\Services\CreditHistoryXlsxExport;
use App\Services\StaffCreditHourService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CreditHistoryExportController extends Controller
{
    public function export(Request $request): BinaryFileResponse
    {
        $staffID = trim((string)($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? ''));
        abort_if($staffID === '', 403, 'Staff EDU login required.');
        $staff = DB::table('staff_edu')->where('staffID', $staffID)->first(['role','status']);
        $role = strtolower(str_replace([' ','-'], '_', (string)($staff->role ?? '')));
        abort_unless($staff && strtolower(trim((string)$staff->status)) === 'active'
            && in_array($role, ['staff_edu','admin'], true), 403);
        $validated = $request->validate(['year'=>['required','integer','min:2000','max:'.((int)date('Y')+1)]]);
        $year = (int)$validated['year'];
        $totals = StaffCreditHourService::totals($staffID, $year);
        $path = tempnam(sys_get_temp_dir(), 'th_credit_');
        abort_unless($path !== false, 500, 'Unable to create export file.');
        try {
            CreditHistoryXlsxExport::write($path, $staffID, $year, $totals);
            $safeId = preg_replace('/[^A-Za-z0-9_-]/', '_', $staffID);
            return response()->download($path, 'TrainHub_Credit_History_'.$safeId.'_'.$year.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store, max-age=0',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }
}
