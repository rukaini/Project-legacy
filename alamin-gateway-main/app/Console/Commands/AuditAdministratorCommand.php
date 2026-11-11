<?php

namespace App\Console\Commands;

use App\Services\AdministratorAccountService;
use App\Services\AdministratorProvisioningAuditService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class AuditAdministratorCommand extends Command
{
    protected $signature = 'elit:audit-administrator';

    protected $description = 'Audit protected Administrator canonical identities without creating or changing business data';

    public function handle(
        AdministratorAccountService $administrator,
        AdministratorProvisioningAuditService $audit,
    ): int {
        $email = $administrator->email();
        if ($email === null) {
            if (! $this->input->isInteractive()) {
                $this->error('Configure a valid ELIT_ADMIN_EMAIL before running this audit non-interactively.');

                return SymfonyCommand::FAILURE;
            }

            $email = strtolower(trim((string) $this->ask('Administrator email to audit')));
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $this->error('A valid Administrator email is required. No data was changed.');

                return SymfonyCommand::FAILURE;
            }
        }

        $rows = $audit->audit($email);
        $this->info('ELIT Portal Administrator audit (read-only)');
        $this->table(
            ['Role', 'Status', 'Required canonical data / review', 'Normal workflow'],
            array_map(
                fn (array $row): array => [$row['label'], $row['status'], $row['requirements'], $row['workflow']],
                $rows,
            ),
        );
        $this->warn('No canonical records, passwords, assignments, or relationships were created or changed.');
        $ready = collect($rows)->every(
            fn (array $row): bool => $row['status'] === 'ready for login',
        );

        if (! $ready) {
            $this->error('The Administrator is NOT fully ready for login.');
            $this->line('Provision missing data only after business-owner review, then rerun this audit.');

            return SymfonyCommand::FAILURE;
        }

        $this->info('The Administrator is fully ready for login.');

        return SymfonyCommand::SUCCESS;
    }
}
