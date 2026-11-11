<?php

namespace App\Services;

use App\Exceptions\SsoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GatewaySsoClient
{
    public function redeem(string $code): array
    {
        $gatewayUrl = rtrim((string) config('gateway.url'), '/');
        $clientId = (string) config('gateway.client_id');
        $secret = (string) config('gateway.client_secret');

        if ($gatewayUrl === '' || $clientId === '' || $secret === '') {
            throw new SsoException('Single sign-on is not configured for this system.', 500);
        }
        $scheme = strtolower((string) parse_url($gatewayUrl, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new SsoException('Single sign-on is not configured for this system.', 500);
        }
        if ((bool) config('gateway.require_https') && $scheme !== 'https') {
            throw new SsoException('Single sign-on is not securely configured for this system.', 500);
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withHeaders([
                    'X-Alamin-SSO-Client' => $clientId,
                    'X-Alamin-SSO-Secret' => $secret,
                ])
                ->connectTimeout((int) config('gateway.connect_timeout', 3))
                ->timeout((int) config('gateway.timeout', 8))
                ->post($gatewayUrl.'/sso/redeem', ['code' => $code]);
        } catch (ConnectionException) {
            throw new SsoException('The login portal is temporarily unavailable. Please try again.', 503);
        }

        if (! $response->successful()) {
            $message = $response->status() === 410
                ? 'This sign-in link is invalid, expired, or already used.'
                : 'The login portal could not verify this sign-in request.';
            throw new SsoException($message, $response->status() === 410 ? 401 : 503);
        }

        $payload = $response->json();
        if (! is_array($payload)
            || ($payload['target'] ?? null) !== $clientId
            || ! is_array($payload['identity'] ?? null)
        ) {
            throw new SsoException('The login portal returned an invalid sign-in response.', 503);
        }

        return $payload['identity'];
    }
}
