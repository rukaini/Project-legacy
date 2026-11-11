<?php

namespace App\Services;

use App\Exceptions\SsoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GatewaySsoClient
{
    /**
     * Redeem the one-time code with Al Amin Login (server-to-server).
     *
     * @return array{role:string,source:string,id:string,email:string,name?:string}
     */
    public function redeem(string $code): array
    {
        $url = (string) config('gateway.url');
        $clientId = (string) config('gateway.client_id');
        $secret = (string) config('gateway.client_secret');

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($url === '' || $clientId === '' || $secret === ''
            || ! in_array($scheme, ['http', 'https'], true)
            || (config('gateway.require_https') && $scheme !== 'https')) {
            throw new SsoException('Single sign-on is not securely configured for this system.');
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'X-Alamin-SSO-Client' => $clientId,
                    'X-Alamin-SSO-Secret' => $secret,
                ])
                ->connectTimeout((int) config('gateway.connect_timeout'))
                ->timeout((int) config('gateway.timeout'))
                ->withoutRedirecting()
                ->post($url.'/sso/redeem', ['code' => $code]);
        } catch (ConnectionException) {
            throw new SsoException('Al Amin Login could not be reached. Please try again.');
        }

        if ($response->failed()) {
            $message = $response->json('message');

            throw new SsoException(
                is_string($message) && $message !== '' && mb_strlen($message) <= 200 && $response->status() < 500
                    ? $message
                    : 'We could not complete single sign-on right now. Please try again.'
            );
        }

        $payload = $response->json();
        $identity = is_array($payload) ? ($payload['identity'] ?? null) : null;

        // The code must have been issued for THIS system and still be within its lifetime.
        $targetOk = isset($payload['target']) && hash_equals($clientId, (string) $payload['target']);
        $notExpired = ! isset($payload['expires_at']) || (int) $payload['expires_at'] >= now()->timestamp;

        if (! is_array($identity) || ! $targetOk || ! $notExpired) {
            throw new SsoException('This sign-in link is invalid, expired, or already used.');
        }

        foreach (['role', 'source', 'id', 'email'] as $field) {
            if (! isset($identity[$field]) || ! is_scalar($identity[$field]) || trim((string) $identity[$field]) === '') {
                throw new SsoException('This sign-in link is invalid, expired, or already used.');
            }
        }

        return $identity;
    }
}
