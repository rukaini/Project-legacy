<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class CanonicalIdentityService
{
    public function __construct(
        private CanonicalRoleRegistry $registry,
        private CanonicalIdentityEligibilityService $eligibility,
    ) {}

    /**
     * Resolve every role whose own canonical credential source accepts the
     * password. Matching email addresses are never sufficient on their own.
     */
    public function authenticate(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $roles = [];

        foreach ($this->registry->credentialDomains() as $domain) {
            $this->matchDomain($roles, $domain, $email, $password);

            if ($domain['role'] === 'teacher' && isset($roles['teacher'])) {
                $teacher = $this->rowForIdentity($roles['teacher'], $domain);
                if ($teacher) {
                    $this->matchTeacherLinkedRole($roles, 'observer', 'observer', 'observerID', $teacher);
                    $this->matchTeacherLinkedRole(
                        $roles, 'external_observer', 'external_observer', 'externalObserverID', $teacher,
                    );
                }
            }
        }

        // Public has no activity column. Its unique profile and canonical role assignment are the eligibility gates.
        if ($this->registry->publicSchemaIsAvailable()
            && ($user = $this->matchingRow('users', 'email', 'password', $email, $password))
            && $this->registry->publicRoleIsAssigned($user->id)
        ) {
            $outsiders = DB::table('outsider')->where('user_id', $user->id)->limit(2)->get();
            if ($outsiders->count() === 1) {
                $outsider = $outsiders->first();
                $name = trim((string) ($outsider->name ?? '')) ?: (string) $user->name;
                $roles['outsider'] = $this->identity(
                    'outsider', 'outsider', $outsider->outsider_id, $name, $user->email,
                    ['user_id' => (string) $user->id],
                );
            }
        }

        return array_filter(
            $roles,
            fn (array $identity, string $role): bool => config("sso.roles.{$role}") !== null,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Re-resolve one session identity against its current canonical role,
     * relationship, and activity rules before security-sensitive use.
     */
    public function revalidate(array $identity): ?array
    {
        $role = (string) ($identity['role'] ?? '');
        if ($role === '' || config("sso.roles.{$role}") === null) {
            return null;
        }

        if ($role === 'outsider') {
            return $this->revalidatePublic($identity);
        }

        if (in_array($role, ['observer', 'external_observer'], true)) {
            return $this->revalidateTeacherLinkedRole($identity, $role);
        }

        $domain = $this->registry->credentialDomain($role);
        if (! is_array($domain) || ($identity['source'] ?? null) !== $domain['table']) {
            return null;
        }

        $row = $this->rowForIdentity($identity, $domain);
        if (! $row || ! $this->registry->credentialIsEligible($domain, $row)) {
            return null;
        }

        return $this->identity(
            $role,
            $domain['table'],
            $row->{$domain['key']},
            $row->{$domain['name']},
            $row->{$domain['email']},
        );
    }

    private function matchDomain(
        array &$roles,
        array $domain,
        string $email,
        string $password,
    ): void {
        $required = array_merge(
            [$domain['key'], $domain['name'], $domain['email'], $domain['password']],
            $domain['required'],
        );
        if (! $this->registry->hasColumns($domain['table'], $required)) {
            return;
        }

        $row = $this->matchingRow(
            $domain['table'],
            $domain['email'],
            $domain['password'],
            $email,
            $password,
        );
        if (! $row || ! $this->registry->credentialIsEligible($domain, $row)) {
            return;
        }

        $roles[$domain['role']] = $this->identity(
            $domain['role'],
            $domain['table'],
            $row->{$domain['key']},
            $row->{$domain['name']},
            $row->{$domain['email']},
        );
    }

    private function matchingRow(
        string $table,
        string $emailColumn,
        string $passwordColumn,
        string $email,
        string $password,
    ): ?object {
        if (! $this->registry->hasColumns($table, [$emailColumn, $passwordColumn])) {
            return null;
        }

        $matches = DB::table($table)
            ->whereRaw('LOWER(TRIM(`'.$emailColumn.'`)) = ?', [$email])
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        $row = $matches->first();

        return $this->passwordMatches($password, (string) ($row->{$passwordColumn} ?? '')) ? $row : null;
    }

    private function passwordMatches(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }

        try {
            return Hash::check($plain, $stored);
        } catch (RuntimeException) {
            return false;
        }
    }

    private function matchTeacherLinkedRole(
        array &$roles,
        string $role,
        string $table,
        string $idColumn,
        object $teacher,
    ): void {
        if (! $this->registry->hasColumns($table, [$idColumn, 'teacherID', 'status'])) {
            return;
        }

        $matches = DB::table($table)
            ->where('teacherID', $teacher->teacherID)
            ->whereRaw('LOWER(TRIM(`status`)) IN (?, ?)', ['active', 'aktif'])
            ->limit(2)
            ->get();
        if ($matches->count() !== 1) {
            return;
        }

        $linked = $matches->first();
        $roles[$role] = $this->identity(
            $role, $table, $linked->{$idColumn}, $teacher->teacherName, $teacher->email,
            ['teacher_id' => (string) $teacher->teacherID],
        );
    }

    private function rowForIdentity(array $identity, array $domain): ?object
    {
        $required = array_merge(
            [$domain['key'], $domain['name'], $domain['email'], $domain['password']],
            $domain['required'],
        );
        if (! $this->registry->hasColumns($domain['table'], $required)) {
            return null;
        }

        $id = (string) ($identity['id'] ?? '');
        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        if ($id === '' || $email === '') {
            return null;
        }

        $rows = DB::table($domain['table'])
            ->where($domain['key'], $id)
            ->whereRaw('LOWER(TRIM(`'.$domain['email'].'`)) = ?', [$email])
            ->limit(2)
            ->get();

        return $rows->count() === 1 ? $rows->first() : null;
    }

    private function revalidateTeacherLinkedRole(array $identity, string $role): ?array
    {
        $table = $role;
        $idColumn = $role === 'observer' ? 'observerID' : 'externalObserverID';
        if (($identity['source'] ?? null) !== $table
            || ! $this->registry->hasColumns($table, [$idColumn, 'teacherID', 'status'])) {
            return null;
        }

        $teacherDomain = $this->registry->credentialDomain('teacher');
        if (! is_array($teacherDomain)) {
            return null;
        }

        $teacherIdentity = [
            'id' => (string) ($identity['teacher_id'] ?? ''),
            'email' => (string) ($identity['email'] ?? ''),
        ];
        $teacher = $this->rowForIdentity($teacherIdentity, $teacherDomain);
        if (! $teacher || ! $this->registry->credentialIsEligible($teacherDomain, $teacher)) {
            return null;
        }

        $rows = DB::table($table)
            ->where($idColumn, (string) ($identity['id'] ?? ''))
            ->where('teacherID', $teacher->teacherID)
            ->limit(2)
            ->get();
        if ($rows->count() !== 1 || ! $this->eligibility->isActive($rows->first()->status ?? null)) {
            return null;
        }

        return $this->identity(
            $role,
            $table,
            $rows->first()->{$idColumn},
            $teacher->teacherName,
            $teacher->email,
            ['teacher_id' => (string) $teacher->teacherID],
        );
    }

    private function revalidatePublic(array $identity): ?array
    {
        if (($identity['source'] ?? null) !== 'outsider' || ! $this->registry->publicSchemaIsAvailable()) {
            return null;
        }

        $userId = (string) ($identity['user_id'] ?? '');
        $outsiderId = (string) ($identity['id'] ?? '');
        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        if ($userId === '' || $outsiderId === '' || $email === '') {
            return null;
        }

        $users = DB::table('users')
            ->where('id', $userId)
            ->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])
            ->limit(2)
            ->get();
        $profiles = DB::table('outsider')->where('user_id', $userId)->limit(2)->get();
        if ($users->count() !== 1
            || $profiles->count() !== 1
            || (string) $profiles->first()->outsider_id !== $outsiderId
            || ! $this->registry->publicRoleIsAssigned($userId)) {
            return null;
        }

        $user = $users->first();
        $profile = $profiles->first();
        $name = trim((string) ($profile->name ?? '')) ?: (string) $user->name;

        return $this->identity(
            'outsider',
            'outsider',
            $profile->outsider_id,
            $name,
            $user->email,
            ['user_id' => (string) $user->id],
        );
    }

    private function identity(
        string $role,
        string $source,
        mixed $id,
        mixed $name,
        mixed $email,
        array $extra = [],
    ): array {
        return $extra + [
            'role' => $role,
            'source' => $source,
            'id' => (string) $id,
            'name' => trim((string) $name),
            'email' => strtolower(trim((string) $email)),
        ];
    }
}
