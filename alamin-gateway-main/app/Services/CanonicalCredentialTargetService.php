<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CanonicalCredentialTargetService
{
    public function __construct(private CanonicalRoleRegistry $registry) {}

    /**
     * @return array<string, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>
     */
    public function resolveForRecovery(string $identifier, bool $isEmail): array
    {
        $targets = [];

        foreach ($this->domains() as $domain) {
            $identifierColumn = $isEmail ? $domain['email'] : $domain['ic'];
            if (! is_string($identifierColumn)) {
                continue;
            }

            $required = array_merge(
                [$domain['key'], $identifierColumn, $domain['email'], $domain['password']],
                $domain['required'],
            );
            if (! $this->hasColumns($domain['table'], $required)) {
                continue;
            }

            $query = DB::table($domain['table']);
            if ($isEmail) {
                $query->whereRaw('LOWER(TRIM(`'.$identifierColumn.'`)) = ?', [strtolower($identifier)]);
            } else {
                $query->whereRaw('TRIM(`'.$identifierColumn.'`) = ?', [$identifier]);
            }

            foreach ($query->lockForUpdate()->limit(3)->get() as $row) {
                $this->addTarget($targets, $domain, $row, $this->eligible($domain['role'], $row, true));
            }
        }

        $this->addPublicRecoveryTargets($targets, $identifier, $isEmail);

        return $targets;
    }

    /**
     * Inspect every credential-bearing canonical source for an email.
     *
     * @return array<string, array{schema_available: bool, conflict: bool, targets: array<int, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>}>
     */
    public function inspectEmail(string $email, bool $lockForUpdate = false): array
    {
        $email = strtolower(trim($email));
        $inspection = [];

        foreach ($this->domains() as $domain) {
            $required = array_merge(
                [$domain['key'], $domain['name'], $domain['email'], $domain['password']],
                $domain['required'],
            );
            $available = $this->hasColumns($domain['table'], $required);
            $targets = [];

            if ($available) {
                $query = DB::table($domain['table'])
                    ->whereRaw('LOWER(TRIM(`'.$domain['email'].'`)) = ?', [$email])
                    ->limit(3);
                if ($lockForUpdate) {
                    $query->lockForUpdate();
                }

                foreach ($query->get() as $row) {
                    $targets[] = $this->target(
                        $domain,
                        $row,
                        $this->eligible($domain['role'], $row, $lockForUpdate),
                    );
                }
            }

            $inspection[$domain['role']] = [
                'schema_available' => $available,
                'conflict' => count($targets) > 1,
                'targets' => $targets,
            ];
        }

        $inspection['outsider'] = $this->inspectPublicEmail($email, $lockForUpdate);

        return $inspection;
    }

    /**
     * @return array{targets: array<string, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>, conflicts: array<int, string>, ineligible: array<int, string>}
     */
    public function resolveAdministratorTargets(string $email, bool $lockForUpdate = false): array
    {
        $targets = [];
        $conflicts = [];
        $ineligible = [];

        foreach ($this->inspectEmail($email, $lockForUpdate) as $role => $source) {
            if ($source['conflict']) {
                $conflicts[] = $role;

                continue;
            }

            if (count($source['targets']) !== 1) {
                continue;
            }

            $target = $source['targets'][0];
            if (! $target['eligible']) {
                $ineligible[] = $role;

                continue;
            }

            $key = $target['table'].'|'.$target['key_column'].'|'.(string) $target['key_value'];
            $targets[$key] = $target;
        }

        return compact('targets', 'conflicts', 'ineligible');
    }

    /**
     * @return array<int, array{role: string, table: string, key: string, name: string, email: string, password: string, ic: ?string, required: array<int, string>}>
     */
    public function domains(): array
    {
        return $this->registry->credentialDomains();
    }

    private function eligible(string $role, object $row, bool $lockForUpdate): bool
    {
        $domain = $this->registry->credentialDomain($role);

        return is_array($domain) && $this->registry->credentialIsEligible($domain, $row, $lockForUpdate);
    }

    /**
     * @param  array<string, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>  $targets
     * @param  array{role: string, table: string, key: string, name: string, email: string, password: string, ic: ?string, required: array<int, string>}  $domain
     */
    private function addTarget(array &$targets, array $domain, object $row, bool $eligible): void
    {
        $target = $this->target($domain, $row, $eligible);
        $key = $target['table'].'|'.$target['key_column'].'|'.(string) $target['key_value'];
        $targets[$key] = $target;
    }

    /**
     * @param  array{role: string, table: string, key: string, name: string, email: string, password: string, ic: ?string, required: array<int, string>}  $domain
     * @return array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}
     */
    private function target(array $domain, object $row, bool $eligible): array
    {
        return [
            'role' => $domain['role'],
            'table' => $domain['table'],
            'key_column' => $domain['key'],
            'key_value' => $row->{$domain['key']},
            'password_column' => $domain['password'],
            'email' => strtolower(trim((string) ($row->{$domain['email']} ?? ''))),
            'eligible' => $eligible,
        ];
    }

    /**
     * @param  array<string, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>  $targets
     */
    private function addPublicRecoveryTargets(array &$targets, string $identifier, bool $isEmail): void
    {
        if (! $this->registry->publicSchemaIsAvailable()) {
            return;
        }

        if ($isEmail) {
            $users = DB::table('users')
                ->whereRaw('LOWER(TRIM(`email`)) = ?', [strtolower($identifier)])
                ->lockForUpdate()
                ->limit(3)
                ->get();
            foreach ($users as $user) {
                $profile = $this->uniqueOutsiderProfile($user->id, true);
                if ($profile && $this->registry->publicRoleIsAssigned($user->id, true)) {
                    $this->addPublicTarget($targets, $user);
                }
            }

            return;
        }

        if (! Schema::hasColumn('outsider', 'ICNumber')) {
            return;
        }

        $profiles = DB::table('outsider')
            ->whereRaw('TRIM(`ICNumber`) = ?', [$identifier])
            ->lockForUpdate()
            ->limit(3)
            ->get();
        foreach ($profiles as $profile) {
            if (! $this->uniqueOutsiderProfile($profile->user_id, true)
                || ! $this->registry->publicRoleIsAssigned($profile->user_id, true)) {
                continue;
            }

            $users = DB::table('users')->where('id', $profile->user_id)->lockForUpdate()->limit(2)->get();
            if ($users->count() === 1) {
                $this->addPublicTarget($targets, $users->first());
            }
        }
    }

    /**
     * @return array{schema_available: bool, conflict: bool, targets: array<int, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>}
     */
    private function inspectPublicEmail(string $email, bool $lockForUpdate): array
    {
        $available = $this->registry->publicSchemaIsAvailable();
        if (! $available) {
            return ['schema_available' => false, 'conflict' => false, 'targets' => []];
        }

        $query = DB::table('users')->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])->limit(3);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $targets = [];
        $invalidRelationship = false;
        foreach ($query->get() as $user) {
            $profileQuery = DB::table('outsider')->where('user_id', $user->id)->limit(3);
            if ($lockForUpdate) {
                $profileQuery->lockForUpdate();
            }
            $profiles = $profileQuery->get();
            if ($profiles->count() !== 1) {
                $invalidRelationship = true;

                continue;
            }

            $targets[] = [
                'role' => 'outsider',
                'table' => 'users',
                'key_column' => 'id',
                'key_value' => $user->id,
                'password_column' => 'password',
                'email' => strtolower(trim((string) $user->email)),
                'eligible' => $this->registry->publicRoleIsAssigned($user->id, $lockForUpdate),
            ];
        }

        return [
            'schema_available' => true,
            'conflict' => $invalidRelationship || count($targets) > 1,
            'targets' => $targets,
        ];
    }

    private function uniqueOutsiderProfile(mixed $userId, bool $lockForUpdate): ?object
    {
        $query = DB::table('outsider')->where('user_id', $userId)->limit(2);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        $profiles = $query->get();

        return $profiles->count() === 1 ? $profiles->first() : null;
    }

    /**
     * @param  array<string, array{role: string, table: string, key_column: string, key_value: mixed, password_column: string, email: string, eligible: bool}>  $targets
     */
    private function addPublicTarget(array &$targets, object $user): void
    {
        $key = 'users|id|'.(string) $user->id;
        $targets[$key] = [
            'role' => 'outsider',
            'table' => 'users',
            'key_column' => 'id',
            'key_value' => $user->id,
            'password_column' => 'password',
            'email' => strtolower(trim((string) $user->email)),
            'eligible' => true,
        ];
    }

    private function hasColumns(string $table, array $columns): bool
    {
        return $this->registry->hasColumns($table, $columns);
    }
}
