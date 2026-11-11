<?php

namespace App\Services;

class AdministratorAccountService
{
    public function email(): ?string
    {
        $email = strtolower(trim((string) config('sso.administrator.email', '')));

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    public function isConfigured(): bool
    {
        return $this->email() !== null;
    }

    public function matches(?string $email): bool
    {
        $configured = $this->email();
        $candidate = strtolower(trim((string) $email));

        return $configured !== null
            && filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false
            && hash_equals($configured, $candidate);
    }
}
