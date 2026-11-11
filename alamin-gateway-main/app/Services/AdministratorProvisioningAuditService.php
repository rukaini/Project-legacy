<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdministratorProvisioningAuditService
{
    public function __construct(
        private CanonicalCredentialTargetService $targets,
        private CanonicalIdentityEligibilityService $eligibility,
        private CanonicalRoleRegistry $registry,
    ) {}

    /**
     * @return array<int, array{role: string, label: string, status: string, requirements: string, workflow: string}>
     */
    public function audit(string $email): array
    {
        $inspection = $this->targets->inspectEmail(strtolower(trim($email)));
        $results = [];

        foreach (config('sso.roles', []) as $role => $configuration) {
            $label = (string) ($configuration['label'] ?? $role);

            if (in_array($role, ['observer', 'external_observer'], true)) {
                $results[] = $this->linkedObserverResult($role, $label, $inspection['teacher'] ?? null);

                continue;
            }

            $source = $inspection[$role] ?? null;
            if (! is_array($source)) {
                $results[] = $this->result(
                    $role,
                    $label,
                    'manual review',
                    'No audited canonical credential mapping is defined for this configured role.',
                    'Confirm the canonical schema before provisioning.',
                );

                continue;
            }

            $results[] = $this->credentialResult($role, $label, $source);
        }

        return $results;
    }

    private function credentialResult(string $role, string $label, array $source): array
    {
        if (! $source['schema_available']) {
            return $this->result(
                $role,
                $label,
                'schema unavailable',
                $this->requirements($role),
                $this->workflow($role),
            );
        }

        if ($source['conflict']) {
            return $this->result(
                $role,
                $label,
                'conflict',
                'Duplicate rows or an invalid canonical relationship must be resolved manually.',
                $this->workflow($role),
            );
        }

        if ($source['targets'] === []) {
            return $this->result($role, $label, 'missing', $this->requirements($role), $this->workflow($role));
        }

        $target = $source['targets'][0];
        $status = $target['eligible'] ? 'ready for login' : 'not login-ready';
        $requirements = $target['eligible']
            ? 'One unique eligible canonical credential exists.'
            : 'The canonical row exists but its role/status/assignment eligibility must be corrected normally.';

        if ($role === 'outsider' && ! $this->registry->publicRoleIsAssigned($target['key_value'])) {
            $requirements .= ' The canonical outsider model_has_roles relationship also requires review.';
        }

        return $this->result($role, $label, $status, $requirements, $this->workflow($role));
    }

    private function linkedObserverResult(string $role, string $label, ?array $teacherSource): array
    {
        $table = $role === 'observer' ? 'observer' : 'external_observer';
        $key = $role === 'observer' ? 'observerID' : 'externalObserverID';

        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, [$key, 'teacherID', 'status'])) {
            return $this->result(
                $role,
                $label,
                'schema unavailable',
                'Requires a unique active '.$table.' row linked to the Administrator teacherID.',
                'Uses the Teacher credential; observation ownership and assignments still apply.',
            );
        }

        if (! is_array($teacherSource) || $teacherSource['conflict'] || count($teacherSource['targets']) !== 1) {
            return $this->result(
                $role,
                $label,
                'missing dependency',
                'Requires one unambiguous Teacher identity before the linked '.$table.' row can be validated.',
                'Uses the Teacher credential; observation ownership and assignments still apply.',
            );
        }

        $teacher = $teacherSource['targets'][0];
        $rows = DB::table($table)->where('teacherID', $teacher['key_value'])->limit(3)->get();
        if ($rows->count() > 1) {
            $status = 'conflict';
            $requirements = 'Multiple linked rows exist and must be resolved manually.';
        } elseif ($rows->count() === 0) {
            $status = 'missing';
            $requirements = 'Requires a unique active linked row using the real Administrator teacherID.';
        } else {
            $ready = $teacher['eligible'] && $this->eligibility->isActive($rows->first()->status ?? null);
            $status = $ready ? 'ready for login' : 'not login-ready';
            $requirements = $ready
                ? 'One active linked row and an eligible Teacher credential exist.'
                : 'Teacher eligibility and the linked row status must both be active.';
        }

        return $this->result(
            $role,
            $label,
            $status,
            $requirements,
            'Uses the Teacher credential; observation ownership and assignments still apply.',
        );
    }

    private function requirements(string $role): string
    {
        return match ($role) {
            'teacher' => 'Requires real Teacher ID, name, email, hashed password, teacher role, and reviewed school assignment.',
            'new_teacher' => 'Requires real New Teacher ID, name, IC, email, hashed password, new_teacher role, and active status.',
            'outsider' => 'Requires unique users and outsider rows plus the canonical outsider model_has_roles relationship.',
            'principal' => 'Requires real Principal ID, name, IC, email, hashed password, principal role, and active status.',
            'staff_edu' => 'Requires real Staff EDU ID, name, IC, email, hashed password, and active status.',
            'hr' => 'Requires real HR ID, name, email, hashed password, and an accepted HR role value.',
            'trainer' => 'Requires real Trainer ID, name, IC, email, hashed trainerPassword, and active status.',
            default => 'Requires approved role-specific canonical data.',
        };
    }

    private function workflow(string $role): string
    {
        return match ($role) {
            'trainer' => 'Course and training assignments must be made through the normal Trainer workflow.',
            'teacher' => 'School placement and Teacher workflows remain mandatory.',
            'new_teacher' => 'New Teacher placement and onboarding data remain mandatory.',
            'principal' => 'School leadership relationships remain mandatory.',
            'outsider' => 'Profile completion and normal Public workflows still apply.',
            'observer', 'external_observer' => 'Observation ownership and assignments still apply.',
            default => 'Normal role-specific workflow and assignments still apply.',
        };
    }

    private function result(
        string $role,
        string $label,
        string $status,
        string $requirements,
        string $workflow,
    ): array {
        return compact('role', 'label', 'status', 'requirements', 'workflow');
    }
}
