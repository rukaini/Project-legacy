<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\CanonicalSsoUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SsoAuthenticator
{
    /** Roles backed by a canonical domain table (session-only identity unless a users row exists). */
    private const DOMAIN_SOURCES = [
        'teacher' => 'teacher',
        'guru_besar' => 'principal',
        'guru_baru' => 'guru_new',
        'hr'        => 'hr_administrator',
    ];

    /**
     * Revalidate a redeemed gateway identity against local data.
     * Returns null when the identity is unmapped, missing, inactive or mismatched.
     *
     * @param  array{role: string, source: string, id: string, email: string, name: string}  $identity
     */
    public function resolve(array $identity): ?Authenticatable
    {
        $mapped = config('gateway.roles', [])[$identity['role'].'|'.$identity['source']] ?? null;

        if (! is_string($mapped) || Role::tryFrom($mapped) === null) {
            // Role/source only — no email, code or secret — so real values can be confirmed.
            Log::warning('SSO identity role/source is not mapped', [
                'role' => $identity['role'],
                'source' => $identity['source'],
            ]);

            return null;
        }

        if (isset(self::DOMAIN_SOURCES[$mapped])) {
            return $this->resolveDomain(self::DOMAIN_SOURCES[$mapped], $identity);
        }

        return $this->resolveLocalUser($mapped, $identity['email']);
    }

    /** Reuses the auth provider's active-record checks for teacher, principal and guru_new. */
    private function resolveDomain(string $source, array $identity): ?Authenticatable
    {
        $user = Auth::guard('web')->getProvider()->retrieveById('canonical:'.$source.':'.$identity['id']);

        if (! $user || strtolower(trim((string) $user->email)) !== $identity['email']) {
            return null;
        }

        return $user;
    }

    /** HR and Outsider live in the users table and need the matching Spatie role. */
    private function resolveLocalUser(string $role, string $email): ?User
    {
        $user = User::query()->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])->first();

        return $user && ! $user instanceof CanonicalSsoUser && $user->hasRole($role) ? $user : null;
    }
}
