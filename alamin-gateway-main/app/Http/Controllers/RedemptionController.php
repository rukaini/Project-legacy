<?php

namespace App\Http\Controllers;

use App\Services\HandoffService;
use App\Services\SystemRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedemptionController extends Controller
{
    public function __invoke(Request $request, SystemRegistry $registry, HandoffService $handoffs): JsonResponse
    {
        if ((bool) config('sso.require_https') && ! $request->isSecure()) {
            return response()->json(['message' => 'Secure transport is required.'], 400);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]+$/'],
        ]);
        $client = (string) $request->header('X-Alamin-SSO-Client', '');
        $system = $registry->system($client);
        if ($client === '' || ! $system || ($system['enabled'] ?? false) !== true) {
            return response()->json(['message' => 'Invalid handoff.'], 401);
        }
        $provided = (string) $request->header('X-Alamin-SSO-Secret', '');
        $expected = (string) ($system['secret'] ?? '');
        if ($provided === '' || $expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Invalid handoff.'], 401);
        }
        $payload = $handoffs->redeem($data['code'], $client);
        if (! $payload) {
            return response()->json(['message' => 'This sign-in link is invalid, expired, or already used.'], 410);
        }

        return response()->json([
            'identity' => $payload['identity'],
            'target' => $payload['target'],
            'issued_at' => $payload['issued_at'],
            'expires_at' => $payload['expires_at'],
        ]);
    }
}
