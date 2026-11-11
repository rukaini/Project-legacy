<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class AdministratorPasswordResetService
{
    public function __construct(
        private AdministratorAccountService $administrator,
        private AdministratorPasswordService $passwords,
    ) {}

    public function issue(): string
    {
        $email = $this->administrator->email();
        if ($email === null) {
            throw new \RuntimeException('Administrator recovery is not configured.');
        }

        $token = bin2hex(random_bytes(32));
        $ttl = max(60, (int) config('sso.administrator.reset_ttl_seconds', 900));
        $this->store()->put($this->key($token), [
            'email' => $email,
            'expires_at' => now()->timestamp + $ttl,
        ], $ttl);

        return $token;
    }

    public function valid(string $token): bool
    {
        $payload = $this->payload($token);

        return $payload !== null && $this->payloadIsValid($payload);
    }

    public function consume(string $token, string $password): bool
    {
        if (! $this->tokenIsWellFormed($token)) {
            return false;
        }

        $key = $this->key($token);
        $lock = $this->store()->lock('lock:'.$key, 10);

        try {
            return $lock->block(3, function () use ($key, $password): bool {
                $payload = $this->store()->get($key);
                if (! is_array($payload) || ! $this->payloadIsValid($payload)) {
                    return false;
                }

                $this->passwords->reset($password);
                $this->store()->forget($key);

                return true;
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }

    public function revoke(string $token): void
    {
        if ($this->tokenIsWellFormed($token)) {
            $this->store()->forget($this->key($token));
        }
    }

    public function digest(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    private function payload(string $token): ?array
    {
        if (! $this->tokenIsWellFormed($token)) {
            return null;
        }

        $payload = $this->store()->get($this->key($token));

        return is_array($payload) ? $payload : null;
    }

    private function payloadIsValid(array $payload): bool
    {
        $email = $this->administrator->email();
        $storedEmail = strtolower(trim((string) ($payload['email'] ?? '')));

        return $email !== null
            && $storedEmail !== ''
            && hash_equals($email, $storedEmail)
            && (int) ($payload['expires_at'] ?? 0) > now()->timestamp;
    }

    private function tokenIsWellFormed(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    private function key(string $token): string
    {
        return 'administrator-password-reset:'.$this->digest($token);
    }

    private function store(): Repository
    {
        return Cache::store((string) config(
            'sso.administrator.reset_cache_store',
            config('cache.default', 'file'),
        ));
    }
}
