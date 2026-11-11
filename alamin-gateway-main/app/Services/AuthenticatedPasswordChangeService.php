<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthenticatedPasswordChangeService
{
    public function __construct(
        private AdministratorAccountService $administrator,
        private AdministratorPasswordService $administratorPasswords,
        private CanonicalCredentialTargetService $canonicalTargets,
        private CanonicalIdentityService $identities,
    ) {}

    /**
     * @param  array<string, mixed>  $identity
     * @return array{administrator: bool, target_count: int}
     */
    public function change(array $identity, string $currentPassword, string $newPassword): array
    {
        if ($currentPassword === $newPassword) {
            throw ValidationException::withMessages([
                'password' => 'The new password must be different from your current password.',
            ]);
        }

        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_array($identity['roles'] ?? null)) {
            throw new RuntimeException('The authenticated account state is invalid. Please sign in again.');
        }

        return DB::transaction(function () use ($identity, $email, $currentPassword, $newPassword): array {
            $sessionTargets = $this->resolveSessionTargets($identity, $email);
            $this->verifyCurrentPassword($sessionTargets, $currentPassword);

            if ($this->administrator->matches($email)) {
                return $this->changeAdministrator($email, $currentPassword, $newPassword);
            }

            foreach ($sessionTargets as $target) {
                $this->updateAndVerify($target, $newPassword);
            }

            return ['administrator' => false, 'target_count' => count($sessionTargets)];
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $identity
     * @return array<string, array{role: string, table: string, key_column: string, key_value: mixed, email_column: string, password_column: string, stored_hash: string}>
     */
    private function resolveSessionTargets(array $identity, string $accountEmail): array
    {
        $targets = [];

        foreach ($identity['roles'] as $roleKey => $roleIdentity) {
            if (! is_string($roleKey) || ! is_array($roleIdentity)) {
                throw new RuntimeException('The authenticated role state is invalid. Please sign in again.');
            }

            $roleIdentity = $this->identities->revalidate($roleIdentity);
            if (! is_array($roleIdentity) || ($roleIdentity['role'] ?? null) !== $roleKey) {
                throw new RuntimeException('An authenticated role is no longer eligible. No passwords were changed.');
            }

            $definition = $this->definition($roleKey, $roleIdentity);
            if ($definition === null) {
                throw new RuntimeException('An authenticated role has no credential target. Please sign in again.');
            }

            [$table, $keyColumn, $keyValue, $emailColumn, $passwordColumn] = $definition;
            if (! Schema::hasTable($table)
                || ! Schema::hasColumns($table, [$keyColumn, $emailColumn, $passwordColumn])) {
                throw new RuntimeException('A canonical credential source is unavailable. No passwords were changed.');
            }

            $rows = DB::table($table)->where($keyColumn, $keyValue)->lockForUpdate()->limit(2)->get();
            if ($rows->count() !== 1) {
                throw new RuntimeException('A canonical credential target could not be uniquely resolved. No passwords were changed.');
            }

            $row = $rows->first();
            $rowEmail = strtolower(trim((string) ($row->{$emailColumn} ?? '')));
            $roleEmail = strtolower(trim((string) ($roleIdentity['email'] ?? '')));
            if ($rowEmail === '' || ! hash_equals($accountEmail, $rowEmail) || ! hash_equals($accountEmail, $roleEmail)) {
                throw new RuntimeException('A canonical credential target no longer belongs to this account. No passwords were changed.');
            }

            $targetKey = $table.'|'.$keyColumn.'|'.(string) $keyValue;
            $targets[$targetKey] = [
                'role' => $roleKey,
                'table' => $table,
                'key_column' => $keyColumn,
                'key_value' => $keyValue,
                'email_column' => $emailColumn,
                'password_column' => $passwordColumn,
                'stored_hash' => (string) ($row->{$passwordColumn} ?? ''),
            ];
        }

        if ($targets === []) {
            throw new RuntimeException('No authenticated credential targets were found. Please sign in again.');
        }

        return $targets;
    }

    /**
     * @param  array<string, mixed>  $identity
     * @return array{0: string, 1: string, 2: mixed, 3: string, 4: string}|null
     */
    private function definition(string $role, array $identity): ?array
    {
        if (($identity['role'] ?? null) !== $role) {
            return null;
        }

        return match ($role) {
            'staff_edu' => $this->directDefinition($identity, 'staff_edu', 'staff_edu', 'staffID', 'email', 'password'),
            'trainer' => $this->directDefinition($identity, 'trainer', 'trainer', 'trainerID', 'trainerEmail', 'trainerPassword'),
            'teacher' => $this->directDefinition($identity, 'teacher', 'teacher', 'teacherID', 'email', 'password'),
            'new_teacher' => $this->directDefinition($identity, 'guru_new', 'guru_new', 'gn_id', 'email', 'password'),
            'principal' => $this->directDefinition($identity, 'principal', 'principal', 'principalID', 'email', 'password'),
            'hr' => $this->directDefinition($identity, 'hr_administrator', 'hr_administrator', 'hrid', 'email', 'password'),
            'observer' => $this->teacherLinkedDefinition($identity, 'observer'),
            'external_observer' => $this->teacherLinkedDefinition($identity, 'external_observer'),
            'outsider' => $this->publicDefinition($identity),
            default => null,
        };
    }

    /** @return array{0: string, 1: string, 2: mixed, 3: string, 4: string}|null */
    private function directDefinition(
        array $identity,
        string $expectedSource,
        string $table,
        string $keyColumn,
        string $emailColumn,
        string $passwordColumn,
    ): ?array {
        $id = (string) ($identity['id'] ?? '');

        return ($identity['source'] ?? null) === $expectedSource && $id !== ''
            ? [$table, $keyColumn, $id, $emailColumn, $passwordColumn]
            : null;
    }

    /** @return array{0: string, 1: string, 2: mixed, 3: string, 4: string}|null */
    private function teacherLinkedDefinition(array $identity, string $expectedSource): ?array
    {
        $teacherId = (string) ($identity['teacher_id'] ?? '');

        return ($identity['source'] ?? null) === $expectedSource && $teacherId !== ''
            ? ['teacher', 'teacherID', $teacherId, 'email', 'password']
            : null;
    }

    /** @return array{0: string, 1: string, 2: mixed, 3: string, 4: string}|null */
    private function publicDefinition(array $identity): ?array
    {
        $userId = (string) ($identity['user_id'] ?? '');

        return ($identity['source'] ?? null) === 'outsider' && $userId !== ''
            ? ['users', 'id', $userId, 'email', 'password']
            : null;
    }

    /**
     * @param  array<string, array{stored_hash: string}>  $targets
     */
    private function verifyCurrentPassword(array $targets, string $currentPassword): void
    {
        foreach ($targets as $target) {
            if ($target['stored_hash'] === '' || ! Hash::check($currentPassword, $target['stored_hash'])) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ]);
            }
        }
    }

    /** @return array{administrator: bool, target_count: int} */
    private function changeAdministrator(string $email, string $currentPassword, string $newPassword): array
    {
        $resolution = $this->canonicalTargets->resolveAdministratorTargets($email, true);
        if ($resolution['conflicts'] !== [] || $resolution['ineligible'] !== []) {
            throw new RuntimeException('The Administrator credential set is not safe to update. No passwords were changed.');
        }

        if (count($resolution['targets']) < AdministratorPasswordService::MINIMUM_TARGETS) {
            throw new RuntimeException('The Administrator credential set is incomplete. No passwords were changed.');
        }

        foreach ($resolution['targets'] as $target) {
            $stored = DB::table($target['table'])
                ->where($target['key_column'], $target['key_value'])
                ->value($target['password_column']);
            if (! is_string($stored) || ! Hash::check($currentPassword, $stored)) {
                throw ValidationException::withMessages([
                    'current_password' => 'The current password is incorrect.',
                ]);
            }
        }

        $result = $this->administratorPasswords->reset($newPassword);

        return ['administrator' => true, 'target_count' => $result['target_count']];
    }

    /**
     * @param  array{role: string, table: string, key_column: string, key_value: mixed, email_column: string, password_column: string, stored_hash: string}  $target
     */
    private function updateAndVerify(array $target, string $newPassword): void
    {
        $hash = Hash::make($newPassword);
        $updated = DB::table($target['table'])
            ->where($target['key_column'], $target['key_value'])
            ->update([$target['password_column'] => $hash]);
        if ($updated !== 1) {
            throw new RuntimeException('A canonical credential update failed. All changes were rolled back.');
        }

        $stored = DB::table($target['table'])
            ->where($target['key_column'], $target['key_value'])
            ->value($target['password_column']);
        if (! is_string($stored) || $stored === $newPassword || ! Hash::check($newPassword, $stored)) {
            throw new RuntimeException('A canonical credential update could not be verified. All changes were rolled back.');
        }
    }
}
