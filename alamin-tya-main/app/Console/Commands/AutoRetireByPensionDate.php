<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoRetireByPensionDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pension:auto-retire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically retire staff when pension date is reached';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = date('Y-m-d');
        $retiredCount = 0;
        $errors = [];

        $this->info('==========================================');
        $this->info('Auto-Retirement Check - ' . $today);
        $this->info('==========================================');
        
        // ============ PROCESS TEACHERS ============
        $this->newLine();
        $this->info('📚 Checking Teachers...');
        
        $teachers = DB::table('teacher')
            ->whereNotNull('pensionDate')
            ->where('pensionDate', '<=', $today)
            ->whereNotIn('teacherID', function($query) {
                $query->select('teacherID')->from('assign')->where('status', 'Berhenti');
            })
            ->get();
        
        $this->info("Found {$teachers->count()} teachers to retire");
        
        foreach ($teachers as $teacher) {
            try {
                DB::beginTransaction();
                
                // Update assign status to Berhenti
                DB::table('assign')
                    ->where('teacherID', $teacher->teacherID)
                    ->update(['status' => 'Berhenti']);
                
                // Log to audit
                $auditID = 'AUTO_' . time() . '_' . $teacher->teacherID;
                DB::table('teacher_audit')->insert([
                    'auditID' => $auditID,
                    'teacherID' => $teacher->teacherID,
                    'status' => 'Berhenti',
                    'action' => 'Auto Retirement',
                    'oldData' => json_encode(['status' => 'Aktif']),
                    'newData' => json_encode([
                        'status' => 'Berhenti', 
                        'pensionDate' => $teacher->pensionDate,
                        'auto_retired_on' => $today
                    ]),
                    'actionDate' => now(),
                ]);
                
                DB::commit();
                $retiredCount++;
                $this->info("  ✓ Retired: {$teacher->teacherName} ({$teacher->teacherID})");
                
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Failed to retire teacher {$teacher->teacherID}: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->error("  ✗ {$errorMsg}");
                Log::error($errorMsg);
            }
        }
        
        // ============ PROCESS STAFF ============
        $this->newLine();
        $this->info('👔 Checking Staff...');
        
        $staff = DB::table('staff')
            ->whereNotNull('pensionDate')
            ->where('pensionDate', '<=', $today)
            ->where('status', '!=', 'Berhenti')
            ->get();
        
        $this->info("Found {$staff->count()} staff to retire");
        
        foreach ($staff as $staffMember) {
            try {
                DB::beginTransaction();
                
                DB::table('staff')
                    ->where('staffID', $staffMember->staffID)
                    ->update(['status' => 'Berhenti']);
                
                DB::commit();
                $retiredCount++;
                $this->info("  ✓ Retired: {$staffMember->staffName} ({$staffMember->staffID})");
                
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Failed to retire staff {$staffMember->staffID}: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->error("  ✗ {$errorMsg}");
                Log::error($errorMsg);
            }
        }
        
        // ============ PROCESS PRINCIPALS ============
        $this->newLine();
        $this->info('👑 Checking Principals...');
        
        $principals = DB::table('principal')
            ->whereNotNull('pensionDate')
            ->where('pensionDate', '<=', $today)
            ->where('status', '!=', 'Berhenti')
            // Add this condition: only process if resignation is approved
            ->where('resignation_request_status', 'approved')
            ->get();
        
        $this->info("Found {$principals->count()} principals to retire");
        
        foreach ($principals as $principal) {
            try {
                DB::beginTransaction();
                
                // Update principal status to Berhenti
                DB::table('principal')
                    ->where('principalID', $principal->principalID)
                    ->update([
                        'status' => 'Berhenti',
                        'resignation_request_status' => 'completed' // Optional: mark as completed
                    ]);
                
                // Optional: Log to audit table
                $auditID = 'AUTO_' . time() . '_' . $principal->principalID;
                DB::table('principal_audit')->insert([
                    'auditID' => $auditID,
                    'principalID' => $principal->principalID,
                    'action' => 'Auto Retirement',
                    'oldData' => json_encode([
                        'status' => 'Aktif',
                        'resignation_request_status' => 'approved'
                    ]),
                    'newData' => json_encode([
                        'status' => 'Berhenti',
                        'resignation_request_status' => 'completed',
                        'pensionDate' => $principal->pensionDate,
                        'auto_retired_on' => $today
                    ]),
                    'actionDate' => now(),
                ]);
                
                DB::commit();
                $retiredCount++;
                $this->info("  ✓ Retired: {$principal->principalName} ({$principal->principalID})");
                
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Failed to retire principal {$principal->principalID}: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->error("  ✗ {$errorMsg}");
                Log::error($errorMsg);
            }
        }
        
        // ============ SUMMARY ============
        $this->newLine();
        $this->info('==========================================');
        $this->info('SUMMARY');
        $this->info('==========================================');
        $this->info("Total retired today: {$retiredCount}");
        
        if (count($errors) > 0) {
            $this->warn("\nErrors encountered: " . count($errors));
            foreach ($errors as $error) {
                $this->error($error);
            }
        } else {
            $this->info("\n✅ No errors encountered.");
        }
        
        // Log summary
        Log::info("Auto-retirement completed", [
            'date' => $today,
            'retired_count' => $retiredCount,
            'teachers' => $teachers->count(),
            'staff' => $staff->count(),
            'principals' => $principals->count(),
            'errors' => count($errors)
        ]);
        
        $this->newLine();
        
        return Command::SUCCESS;
    }
}