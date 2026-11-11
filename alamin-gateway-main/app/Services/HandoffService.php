<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class HandoffService
{
    public function issue(string $target, array $identity): string
    {
        $code = bin2hex(random_bytes(32));
        $now = now()->timestamp;
        $ttl = max(30, (int) config('sso.ttl_seconds', 90));
        $this->store()->put($this->key($code), [
            'target' => $target,
            'identity' => $identity,
            'issued_at' => $now,
            'expires_at' => $now + $ttl,
        ], $ttl);

        return $code;
    }

    public function redeem(string $code, string $target): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $code)) {
            return null;
        }

        $key = $this->key($code);
        $lock = $this->store()->lock('lock:'.$key, 5);
        try {
            return $lock->block(2, function () use ($key, $target): ?array {
                $payload = $this->store()->get($key);
                if (! is_array($payload)
                    || ! hash_equals((string) ($payload['target'] ?? ''), $target)
                    || (int) ($payload['expires_at'] ?? 0) <= now()->timestamp
                ) {
                    return null;
                }
                $this->store()->forget($key);

                return $payload;
            });
        } catch (LockTimeoutException) {
            return null;
        }
    }

    private function key(string $code): string
    {
        return 'sso:code:'.hash('sha256', $code);
    }

    private function store(): Repository
    {
        return Cache::store((string) config('sso.cache_store', 'file'));
    }
}
