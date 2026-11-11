<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GatewaySsoClient
{
    public function redeem(string $code): array
    {
        $url = rtrim((string) config('gateway.url'), '/');

        if (config('gateway.require_https') && ! str_starts_with($url, 'https://')) {
            throw new Exception('Single sign-on is not securely configured for this system.');
        }

        $response = Http::withHeaders([
            'X-Alamin-SSO-Client' => config('gateway.client_id'),
            'X-Alamin-SSO-Secret' => config('gateway.client_secret'),
        ])->post($url . '/sso/redeem', [
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new Exception(
                $response->json('message')
                ?? 'We could not complete single sign-on right now.'
            );
        }

        $identity = $response->json('identity');

        if (! is_array($identity)) {
            throw new Exception('Invalid response from Al Amin Login.');
        }

        return $identity;
    }
}