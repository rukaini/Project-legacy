<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PasswordRecoveryService
{
    public function __construct(
        private CanonicalCredentialTargetService $targets,
        private AdministratorAccountService $administrator,
        private SecurePasswordGenerator $passwords,
    ) {}

    /**
     * @return array{status: 'success'|'not_found'|'ambiguous'|'administrator_email'|'administrator_ic', password?: string}
     */
    public function recover(string $input): array
    {
        $identifier = trim($input);
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $identifier = strtolower($identifier);
        }

        return DB::transaction(function () use ($identifier, $isEmail): array {
            $targets = $this->targets->resolveForRecovery($identifier, $isEmail);

            foreach ($targets as $target) {
                if ($this->administrator->matches($target['email'])) {
                    return ['status' => $isEmail ? 'administrator_email' : 'administrator_ic'];
                }
            }

            $targets = array_filter($targets, fn (array $target): bool => $target['eligible']);

            if ($targets === []) {
                return ['status' => 'not_found'];
            }

            if (count($targets) !== 1) {
                return ['status' => 'ambiguous'];
            }

            $password = $this->passwords->generate(8, 4);
            $hash = Hash::make($password);
            $target = reset($targets);

            $rows = DB::table($target['table'])
                ->where($target['key_column'], $target['key_value'])
                ->lockForUpdate()
                ->limit(2)
                ->get([$target['key_column'], $target['password_column']]);

            if ($rows->count() !== 1) {
                throw new RuntimeException('The recovery credential target is no longer unique.');
            }

            $updated = DB::table($target['table'])
                ->where($target['key_column'], $target['key_value'])
                ->update([$target['password_column'] => $hash]);

            if ($updated !== 1) {
                throw new RuntimeException('The recovery credential update affected an unexpected number of rows.');
            }

            $stored = DB::table($target['table'])
                ->where($target['key_column'], $target['key_value'])
                ->value($target['password_column']);

            if (! is_string($stored) || $stored === $password || ! Hash::check($password, $stored)) {
                throw new RuntimeException('The recovery credential update could not be verified.');
            }

            return ['status' => 'success', 'password' => $password];
        }, 3);
    }
}
