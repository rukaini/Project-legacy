<?php

namespace App\Services;

use App\Models\Training;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainingService
{
    public function syncStatuses(): void
    {
        $today = now()->toDateString();

        Training::with('courseSessions')->get()->each(function (Training $training) use ($today) {
            $sessions = $training->courseSessions;
            if ($sessions->isEmpty()) {
                return;
            }

            $minDate = $sessions->min('session_date')?->toDateString();
            $maxDate = $sessions->max('session_date')?->toDateString();

            $newStatus = match (true) {
                $maxDate < $today => 'completed',
                $minDate > $today => 'upcoming',
                default           => 'ongoing',
            };

            if ($training->status !== $newStatus) {
                $training->update(['status' => $newStatus]);
            }
        });
    }

    public function markCompleted(Training $training, User $guruBesar): void
    {
        DB::transaction(function () use ($training) {
            $training->update(['status' => 'completed']);
        });

        // audit_logs.target_id is a non-null bigint while courseID is a
        // canonical string key. Keep audit incompatibility outside the core
        // transaction and never invent a lossy numeric target.
        try {
            Log::warning('Canonical database audit row skipped for a string target.', [
                'user_id'     => $guruBesar->id,
                'action'      => 'training.completed',
                'target_type' => Training::class,
                'domain_id'   => $training->getKey(),
                'metadata'    => [
                    'courseID'   => $training->getKey(),
                    'courseName' => $training->courseName,
                ],
            ]);
        } catch (\Throwable) {
            // Audit incompatibility must never undo the completed status.
        }
    }
}
