<?php

namespace App\Services;

use App\Exceptions\SsoException;
use Illuminate\Support\Facades\Http;

class GatewaySsoClient
{
    /**
     * Redeem a one-time handoff code server-to-server.
     *
     * @return array{role: string, source: string, id: string, email: string, name: string}
     */
    public function redeem(string $code): array
    {
        $base = (string) config('gateway.url');
        $clientId = (string) config('gateway.client_id');
        $secret = (string) config('gateway.client_secret');

        $secure = str_starts_with(strtolower($base), 'https://');
        if ($base === '' || $clientId === '' || $secret === ''
            || ! filter_var($base, FILTER_VALIDATE_URL)
            || (config('gateway.require_https') && ! $secure)) {
            throw new SsoException('Single sign-on is not securely configured for this system.');
        }

        $response = Http::withHeaders([
                'X-Alamin-SSO-Client' => $clientId,
                'X-Alamin-SSO-Secret' => $secret,
            ])
            ->acceptJson()
            ->connectTimeout((int) config('gateway.connect_timeout'))
            ->timeout((int) config('gateway.timeout'))
            ->post(rtrim($base, '/').'/sso/redeem', ['code' => $code]);

        if (! $response->successful()) {
            $message = $response->json('message');

            throw new SsoException(
                is_string($message) && $message !== ''
                    ? $message
                    : 'We could not complete single sign-on right now. Please try again.'
            );
        }

        $payload = $response->json();
        $identity = is_array($payload) ? ($payload['identity'] ?? null) : null;

        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if (! is_array($identity)
            || ($payload['target'] ?? null) !== $clientId
            || $expiresAt < now()->timestamp) {
            throw new SsoException('This sign-in link is invalid, expired, or already used.');
        }

        foreach (['role', 'source', 'id', 'email'] as $field) {
            if (! isset($identity[$field]) || ! is_scalar($identity[$field]) || trim((string) $identity[$field]) === '') {
                throw new SsoException('This sign-in link is invalid, expired, or already used.');
            }
        }

        return [
            'role' => strtolower(trim((string) $identity['role'])),
            'source' => strtolower(trim((string) $identity['source'])),
            'id' => trim((string) $identity['id']),
            'email' => strtolower(trim((string) $identity['email'])),
            'name' => trim((string) ($identity['name'] ?? '')),
        ];
    }
}
