<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdministratorPasswordService
{
    public const MINIMUM_TARGETS = 2;

    public function __construct(
        private AdministratorAccountService $administrator,
        private CanonicalCredentialTargetService $targets,
    ) {}

    /**
     * @return array{email: string, roles: array<int, string>, target_count: int}
     */
    public function reset(string $password): array
    {
        $email = $this->administrator->email();
        if ($email === null) {
            throw new RuntimeException('ELIT_ADMIN_EMAIL must contain a valid email address.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('The Administrator password must be at least 12 characters.');
        }

        return DB::transaction(function () use ($email, $password): array {
            $resolution = $this->targets->resolveAdministratorTargets($email, true);

            if ($resolution['conflicts'] !== []) {
                throw new RuntimeException(
                    'Duplicate or ambiguous Administrator credential targets were found for: '
                    .implode(', ', $resolution['conflicts']).'.',
                );
            }

            if ($resolution['ineligible'] !== []) {
                throw new RuntimeException(
                    'Existing Administrator credential identities are ineligible: '
                    .implode(', ', $resolution['ineligible'])
                    .'. Correct them before password synchronization.',
                );
            }

            if (count($resolution['targets']) < self::MINIMUM_TARGETS) {
                throw new RuntimeException(
                    'At least '.self::MINIMUM_TARGETS.' eligible canonical credential targets are required; found '
                    .count($resolution['targets']).'.',
                );
            }

            $roles = [];
            foreach ($resolution['targets'] as $target) {
                $hash = Hash::make($password);
                $updated = DB::table($target['table'])
                    ->where($target['key_column'], $target['key_value'])
                    ->update([$target['password_column'] => $hash]);

                if ($updated !== 1) {
                    throw new RuntimeException(
                        'A canonical credential update failed for role '.$target['role'].'. All changes were rolled back.',
                    );
                }

                $stored = DB::table($target['table'])
                    ->where($target['key_column'], $target['key_value'])
                    ->value($target['password_column']);
                if (! is_string($stored) || $stored === $password || ! Hash::check($password, $stored)) {
                    throw new RuntimeException(
                        'A canonical credential update could not be verified for role '.$target['role'].'. All changes were rolled back.',
                    );
                }

                $roles[] = $target['role'];
            }

            return [
                'email' => $email,
                'roles' => $roles,
                'target_count' => count($resolution['targets']),
            ];
        }, 3);
    }
}
