<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class TrainingQrController extends Controller
{
    public function download(Request $request, string $sessionID): Response
    {
        $staffID = (string) ($_SESSION['staffID'] ?? $_SESSION['staff_id'] ?? '');
        abort_if($staffID === '', 403);
        abort_unless(DB::table('staff_edu')->where('staffID', $staffID)
            ->whereRaw('LOWER(TRIM(COALESCE(status, ?))) = ?', ['active', 'active'])
            ->whereRaw("LOWER(REPLACE(REPLACE(TRIM(role), ' ', '_'), '-', '_')) IN (?, ?)", ['staff_edu','admin'])->exists(), 403);

        $qr = DB::table('qr_session')->where('sessionID', $sessionID)->first(['attendanceLink']);
        abort_unless($qr && !empty($qr->attendanceLink), 404);

        // The QR provider URL is constant; the attendance link comes from our DB,
        // so user-supplied URLs are never fetched (SSRF prevention).
        try {
            $remote = Http::timeout(12)->accept('image/png')->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '440x440',
                'format' => 'png',
                'data' => \App\Support\PublicShortLink::urlFor('attendance', (string) $sessionID),
            ]);
            if (!$remote->successful() || !str_starts_with((string)$remote->header('Content-Type'), 'image/png')) {
                abort(503, 'QR image download is temporarily unavailable.');
            }
            return response($remote->body(), 200, [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'attachment; filename="attendance-'. $sessionID .'.png"',
                'Cache-Control' => 'private, no-store',
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(503, 'QR image provider is currently unreachable.');
        }
    }
}
