<?php

namespace App\Services;

use App\Models\Assign;
use App\Models\HrAdministrator;
use App\Models\Principal;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;

/**
 * Re-validates the identity returned by Al Amin Login against THIS system's
 * real (shared) database and builds the same session data the old login used.
 */
class HrmsSsoAuthenticator
{
    /** role => [model, id column, name column, dashboard route name] */
    private const LOCAL = [
        'hr' => [HrAdministrator::class, 'hrid', 'username', 'hr.dashboard'],
        'principal' => [Principal::class, 'principalID', 'principalName', 'principal.dashboard'],
        'teacher' => [Teacher::class, 'teacherID', 'teacherName', 'teacher.dashboard'],
    ];

    /**
     * @param  array{role:string,source:string,id:string,email:string}  $identity
     * @return array<string,mixed>|null  session data + 'dashboard' route name, or null if not allowed
     */
    public function resolve(array $identity): ?array
    {
        $role = strtolower(trim((string) $identity['role']));
        $source = strtolower(trim((string) $identity['source']));

        // 1. Role must be allowed in this system, and the source must belong to that role.
        if (! in_array($role, (array) config('gateway.allowed_roles'), true)
            || ! isset(self::LOCAL[$role])
            || ! in_array($source, (array) config("gateway.role_sources.$role", []), true)) {
            return null;
        }

        [$modelClass, $idColumn, $nameColumn, $dashboard] = self::LOCAL[$role];

        // 2. The user must really exist in this system's database.
        /** @var Model|null $record */
        $record = $modelClass::query()->where($idColumn, (string) $identity['id'])->first();

        if (! $record) {
            return null;
        }

        // 3. Canonical email must still match what Al Amin Login says.
        if (strcasecmp(trim((string) $record->email), trim((string) $identity['email'])) !== 0) {
            return null;
        }

        // 4. Same "still active" rules as the old login.
        if ($this->isInactive($record, $role, (string) $identity['id'])) {
            return null;
        }

        // Never keep the password hash in the session.
        $sessionUser = clone $record;
        $sessionUser->offsetUnset('password');

        $data = [
            'user' => $sessionUser,
            'userID' => (string) $record->{$idColumn},
            'userName' => $record->{$nameColumn},
            'role' => $role,
            'email' => $record->email,
            'dashboard' => $dashboard,
        ];

        if (isset($record->schoolID)) {
            $data['schoolID'] = $record->schoolID;
        }

        return $data;
    }

    private function isInactive(Model $record, string $role, string $id): bool
    {
        if (isset($record->status) && strtolower((string) $record->status) === 'berhenti') {
            return true;
        }

        if (isset($record->assign_status) && strtolower((string) $record->assign_status) === 'berhenti') {
            return true;
        }

        if (isset($record->is_active) && (int) $record->is_active === 0) {
            return true;
        }

        if ($role === 'teacher'
            && Assign::query()->where('teacherID', $id)->where('status', 'Berhenti')->exists()) {
            return true;
        }

        return false;
    }
}
