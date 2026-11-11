<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CanonicalRoleRegistry
{
    public function __construct(private CanonicalIdentityEligibilityService $eligibility) {}

    /**
     * The canonical credential-bearing sources. Linked Observer roles use the
     * Teacher credential and Public uses the users credential through its
     * outsider profile, so those roles are intentionally handled separately.
     *
     * @return array<int, array{role: string, table: string, key: string, name: string, email: string, password: string, ic: ?string, required: array<int, string>, role_values: ?array<int, string>, status: ?string, password_change_required: ?string}>
     */
    public function credentialDomains(): array
    {
        return [
            [
                'role' => 'staff_edu', 'table' => 'staff_edu', 'key' => 'staffID', 'name' => 'staffName',
                'ic' => 'ICNumber', 'email' => 'email', 'password' => 'password', 'required' => ['status'],
                'role_values' => null, 'status' => 'status', 'password_change_required' => null,
            ],
            [
                'role' => 'teacher', 'table' => 'teacher', 'key' => 'teacherID', 'name' => 'teacherName',
                'ic' => 'ICNumber', 'email' => 'email', 'password' => 'password', 'required' => ['role'],
                'role_values' => ['teacher'], 'status' => null, 'password_change_required' => null,
            ],
            [
                'role' => 'new_teacher', 'table' => 'guru_new', 'key' => 'gn_id', 'name' => 'gn_name',
                'ic' => 'ic_number', 'email' => 'email', 'password' => 'password',
                'required' => ['role', 'current_status'], 'role_values' => ['new_teacher'],
                'status' => 'current_status', 'password_change_required' => null,
            ],
            [
                'role' => 'principal', 'table' => 'principal', 'key' => 'principalID', 'name' => 'principalName',
                'ic' => 'ICNumber', 'email' => 'email', 'password' => 'password', 'required' => ['role', 'status'],
                'role_values' => ['principal'], 'status' => 'status', 'password_change_required' => null,
            ],
            [
                'role' => 'hr', 'table' => 'hr_administrator', 'key' => 'hrid', 'name' => 'username',
                'ic' => null, 'email' => 'email', 'password' => 'password', 'required' => ['role'],
                'role_values' => ['hr', 'hr_administrator'], 'status' => null,
                'password_change_required' => 'password_change_required',
            ],
            [
                'role' => 'trainer', 'table' => 'trainer', 'key' => 'trainerID', 'name' => 'trainerName',
                'ic' => 'trainerIC', 'email' => 'trainerEmail', 'password' => 'trainerPassword',
                'required' => ['status'], 'role_values' => null, 'status' => 'status',
                'password_change_required' => null,
            ],
        ];
    }

    /**
     * @return array{role: string, table: string, key: string, name: string, email: string, password: string, ic: ?string, required: array<int, string>, role_values: ?array<int, string>, status: ?string, password_change_required: ?string}|null
     */
    public function credentialDomain(string $role): ?array
    {
        foreach ($this->credentialDomains() as $domain) {
            if ($domain['role'] === $role) {
                return $domain;
            }
        }

        return null;
    }

    /** @param array{role: string, role_values: ?array<int, string>, status: ?string} $domain */
    public function credentialIsEligible(array $domain, object $row, bool $lockForUpdate = false): bool
    {
        if (is_array($domain['role_values'])) {
            $role = strtolower(trim((string) ($row->role ?? '')));
            if (! in_array($role, $domain['role_values'], true)) {
                return false;
            }
        }

        if (is_string($domain['status'])
            && ! $this->eligibility->isActive($row->{$domain['status']} ?? null)) {
            return false;
        }

        return $domain['role'] !== 'teacher'
            || $this->eligibility->teacherIsActive((string) ($row->teacherID ?? ''), $lockForUpdate);
    }

    public function publicSchemaIsAvailable(): bool
    {
        return $this->hasColumns('users', ['id', 'name', 'email', 'password'])
            && $this->hasColumns('outsider', ['outsider_id', 'user_id'])
            && $this->hasColumns('roles', ['id', 'name', 'guard_name'])
            && $this->hasColumns('model_has_roles', ['role_id', 'model_type', 'model_id']);
    }

    public function publicRoleIsAssigned(mixed $userId, bool $lockForUpdate = false): bool
    {
        if (! $this->publicSchemaIsAvailable()) {
            return false;
        }

        $roleQuery = DB::table('roles')
            ->whereRaw('LOWER(TRIM(`name`)) = ?', ['outsider'])
            ->whereRaw('LOWER(TRIM(`guard_name`)) = ?', ['web'])
            ->limit(2);
        if ($lockForUpdate) {
            $roleQuery->lockForUpdate();
        }
        $roles = $roleQuery->get();
        if ($roles->count() !== 1) {
            return false;
        }

        $assignmentQuery = DB::table('model_has_roles')
            ->where('role_id', $roles->first()->id)
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $userId)
            ->limit(2);
        if ($lockForUpdate) {
            $assignmentQuery->lockForUpdate();
        }

        return $assignmentQuery->get()->count() === 1;
    }

    /** @param array<int, string> $columns */
    public function hasColumns(string $table, array $columns): bool
    {
        return Schema::hasTable($table) && Schema::hasColumns($table, $columns);
    }
}
