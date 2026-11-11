<?php

namespace App\Console\Commands;

use App\Services\AdministratorPasswordService;
use App\Services\SecurePasswordGenerator;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Throwable;

class AdministratorPasswordCommand extends Command
{
    protected $signature = 'elit:administrator-password
        {--generate : Generate a high-entropy password and display it once after a successful reset}';

    protected $description = 'Atomically synchronize the protected Administrator password across legitimate canonical identities';

    public function handle(
        AdministratorPasswordService $passwords,
        SecurePasswordGenerator $generator,
    ): int {
        $generated = (bool) $this->option('generate');

        if ($generated) {
            $password = $generator->generate();
        } else {
            $password = (string) $this->secret('New Administrator password (minimum 12 characters)');
            $confirmation = (string) $this->secret('Confirm new Administrator password');
            if (! hash_equals($password, $confirmation)) {
                $this->error('The password confirmation did not match. No credentials were changed.');

                return SymfonyCommand::FAILURE;
            }
        }

        try {
            $result = $passwords->reset($password);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return SymfonyCommand::FAILURE;
        } catch (Throwable) {
            $this->error('The Administrator password synchronization failed. No partial update was committed.');

            return SymfonyCommand::FAILURE;
        }

        $this->info('Administrator credentials were synchronized atomically.');
        $this->line('Credential targets updated: '.$result['target_count']);
        $this->line('Canonical roles: '.implode(', ', $result['roles']));
        $this->warn('Role-specific workflow data and assignments were not bypassed or changed.');

        if ($generated) {
            $this->newLine();
            $this->warn('Generated password (shown once): '.$password);
        }

        return SymfonyCommand::SUCCESS;
    }
}
