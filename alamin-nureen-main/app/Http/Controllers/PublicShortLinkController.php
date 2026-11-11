<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PublicShortLinkController extends Controller
{
    /** Resolve inside the app, without revealing target IDs in the browser URL. */
    public function open(Request $request, string $kind, string $token): Response
    {
        abort_unless(in_array($kind, ['a','f','p'], true) && preg_match('/^[0-9a-f]{24}$/',$token), 404);
        $row = DB::table('trainhub_short_links')->where('token', $kind.'/'.$token)->first();
        abort_unless($row, 404);
        if ($kind === 'a' && $row->link_type === 'attendance') {
            $request->query->add(['sessionID' => $row->target_id]);
            return app(AttendanceController::class)->index($request);
        }
        $expected = $kind === 'p' ? 'feedback_pic' : 'feedback_participant';
        abort_unless($row->link_type === $expected, 404);
        $request->query->add(['answer' => '1','formID' => $row->target_id]);
        return app(FeedbackController::class)->index($request);
    }
}
