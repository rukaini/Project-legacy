<?php

namespace App\Http\Controllers;

use App\Models\GuruNew;
use App\Models\EnrollGuruBaru;
use App\Models\Training;
use App\Models\TrainingContentBlock;
use App\Models\TrainingContentInteractionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingContentInteractionController extends Controller
{
    /**
     * Log a user interaction with a content block.
     *
     * Event types: open | close | download | read | submit | pause_video
     *
     * Rules:
     *   - first_opened_at is set once (first 'open' only, never overwritten)
     *   - last_accessed_at is updated on every event
     *   - open_count increments only on 'open'
     *   - total_seconds accumulates on 'close' (seconds_spent sent from frontend)
     */
    public function log(Request $request, Training $training, TrainingContentBlock $block): JsonResponse
    {
        $request->validate([
            'event_type'    => ['required', 'string', 'in:open,close,download,read,submit,pause_video'],
            'seconds_spent' => ['nullable', 'integer', 'min:0'],
        ]);

        $user    = $request->user();
        $guruNew = GuruNew::where('email', $user->email)->first();
        abort_if(! $guruNew, 403, 'No applicant profile found.');

        $enrollment = EnrollGuruBaru::firstOrCreate(
            ['gn_id' => $guruNew->gn_id, 'course_id' => $training->getKey()],
            ['status' => 'auto_enrolled', 'applied_at' => now()],
        );

        $eventType    = $request->input('event_type');
        $secondsSpent = (int) $request->input('seconds_spent', 0);

        $log = TrainingContentInteractionLog::where('enrollment_id', $enrollment->enrollment_id)
            ->where('block_id', $block->getKey())
            ->first();

        if (! $log) {
            TrainingContentInteractionLog::create([
                'courseID'       => $training->getKey(),
                'module_id'       => $block->module_id,
                'block_id'        => $block->getKey(),
                'enrollment_id'   => $enrollment->enrollment_id,
                'first_opened_at' => $eventType === 'open' ? now() : null,
                'last_accessed_at'=> now(),
                'open_count'      => $eventType === 'open' ? 1 : 0,
                'total_seconds'   => 0,
                'last_event_type' => $eventType,
            ]);
        } else {
            $updates = [
                'last_accessed_at' => now(),
                'last_event_type'  => $eventType,
            ];

            if ($eventType === 'open') {
                $updates['open_count'] = $log->open_count + 1;
                // first_opened_at is never overwritten after it is set
                if (! $log->first_opened_at) {
                    $updates['first_opened_at'] = now();
                }
            }

            if ($secondsSpent > 0) {
                $updates['total_seconds'] = $log->total_seconds + $secondsSpent;
            }

            $log->update($updates);
        }

        return response()->json(['ok' => true]);
    }
}
