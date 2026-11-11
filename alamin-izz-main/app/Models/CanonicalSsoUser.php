<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Support\Collection;

/**
 * A rehydratable, session-only user backed by a canonical domain table.
 *
 * Some canonical teachers, principals, and Guru Baru do not have a matching
 * users/model_has_roles row. SSO must not create one merely to make login
 * work, so the custom auth provider recreates this identity on every request.
 */
class CanonicalSsoUser extends User
{
    protected $keyType = 'string';

    public $incrementing = false;

    private string $canonicalRole;

    public static function fromDomain(string $source, string $id, string $name, string $email, string $role): self
    {
        $user = new self();
        $user->canonicalRole = $role;
        $user->setRawAttributes([
            'id' => 'canonical:'.$source.':'.$id,
            'name' => $name,
            'email' => $email,
            'external_id' => $id,
            'canonical_source' => $source,
        ], true);
        $user->exists = true;

        return $user;
    }

    public function getRoleNames(): Collection
    {
        return collect([$this->canonicalRole]);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $values = $roles instanceof Collection ? $roles->all() : (array) $roles;

        return in_array($this->canonicalRole, array_map(
            static fn ($role): string => $role instanceof \BackedEnum ? (string) $role->value : (string) $role,
            $values,
        ), true);
    }

    public function hasAnyRole(...$roles): bool
    {
        return $this->hasRole(collect($roles)->flatten());
    }

    public function isGuruBesar(): bool
    {
        return $this->canonicalRole === Role::GuruBesar->value;
    }

    public function isTeacher(): bool
    {
        return $this->canonicalRole === Role::Teacher->value;
    }

    public function isOutsider(): bool
    {
        return false;
    }

    public function isGuruBaru(): bool
    {
        return $this->canonicalRole === Role::GuruBaru->value;
    }

    public function isHR(): bool
    {
        return $this->canonicalRole === Role::HR->value;
    }

    public function isStaffEdu(): bool
    {
        return false;
    }

    public function isParticipant(): bool
    {
        return $this->isTeacher() || $this->isGuruBaru();
    }
}
