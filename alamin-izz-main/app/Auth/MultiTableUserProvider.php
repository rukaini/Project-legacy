<?php

namespace App\Auth;

use App\Enums\Role;
use App\Models\CanonicalSsoUser;
use App\Models\GuruNew;
use App\Models\HrAdministrator;
use App\Models\Principal;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MultiTableUserProvider extends EloquentUserProvider
{
    /** @var array<string, string> */
    private array $domainHashes = [];

    public function retrieveById($identifier): ?Authenticatable
    {
        $identifier = (string) $identifier;

        if (str_starts_with($identifier, 'canonical:')) {
            return $this->retrieveCanonicalSsoUser($identifier);
        }

        return parent::retrieveById($identifier);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = strtolower(trim((string) ($credentials['email'] ?? '')));
        if ($email === '') {
            return null;
        }

        $user = User::query()->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])->first();
        if ($user) {
            return $user;
        }

        $guruNew = GuruNew::query()
            ->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])
            ->whereRaw('LOWER(TRIM(`role`)) = ?', ['new_teacher'])
            ->whereRaw('LOWER(TRIM(`current_status`)) = ?', ['active'])
            ->first();
        if (! $guruNew) {
            return null;
        }

        $this->domainHashes[$email] = (string) $guruNew->getRawOriginal('password');

        return CanonicalSsoUser::fromDomain(
            'guru_new',
            (string) $guruNew->gn_id,
            (string) $guruNew->gn_name,
            (string) $guruNew->email,
            Role::GuruBaru->value,
        );
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $email = strtolower(trim((string) $user->email));

        if (isset($this->domainHashes[$email])) {
            $valid = Hash::check((string) ($credentials['password'] ?? ''), $this->domainHashes[$email]);
            unset($this->domainHashes[$email]);

            return $valid;
        }

        return parent::validateCredentials($user, $credentials);
    }

    private function retrieveCanonicalSsoUser(string $identifier): ?Authenticatable
    {
        [, $source, $id] = array_pad(explode(':', $identifier, 3), 3, '');

        if ($source === 'teacher') {
            $domain = Teacher::query()->whereKey($id)
                ->whereRaw('LOWER(TRIM(`role`)) = ?', ['teacher'])
                ->first();
            $assignment = $domain
                ? DB::table('assign')->where('teacherID', $id)->orderByDesc('assignDate')->first()
                : null;
            $inactive = $assignment && in_array(
                strtolower(trim((string) ($assignment->status ?? ''))),
                ['berhenti', 'inactive', 'tidak aktif'],
                true,
            );

            return $domain && ! $inactive
                ? CanonicalSsoUser::fromDomain('teacher', (string) $domain->teacherID, (string) $domain->teacherName, (string) $domain->email, Role::Teacher->value)
                : null;
        }

        if ($source === 'principal') {
            $domain = Principal::query()->whereKey($id)
                ->whereRaw('LOWER(TRIM(`role`)) = ?', ['principal'])
                ->whereRaw('LOWER(TRIM(`status`)) IN (?, ?)', ['active', 'aktif'])
                ->first();

            return $domain
                ? CanonicalSsoUser::fromDomain('principal', (string) $domain->principalID, (string) $domain->principalName, (string) $domain->email, Role::GuruBesar->value)
                : null;
        }

        if ($source === 'hr_administrator') {
            $domain = HrAdministrator::query()->whereKey($id)->first();

            return $domain
                ? CanonicalSsoUser::fromDomain('hr_administrator', (string) $domain->hrid, (string) $domain->username, (string) $domain->email, Role::HR->value)
                : null;
        }

        if ($source === 'guru_new') {
            $domain = GuruNew::query()->whereKey($id)
                ->whereRaw('LOWER(TRIM(`role`)) = ?', ['new_teacher'])
                ->whereRaw('LOWER(TRIM(`current_status`)) = ?', ['active'])
                ->first();

            return $domain
                ? CanonicalSsoUser::fromDomain('guru_new', (string) $domain->gn_id, (string) $domain->gn_name, (string) $domain->email, Role::GuruBaru->value)
                : null;
        }

        return null;
    }
}
