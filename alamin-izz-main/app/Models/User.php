<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'external_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'email', 'email');
    }

    public function principal(): HasOne
    {
        return $this->hasOne(Principal::class, 'email', 'email');
    }

    public function outsider(): HasOne
    {
        return $this->hasOne(Outsider::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isGuruBesar(): bool
    {
        return $this->hasRole(Role::GuruBesar->value);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(Role::Teacher->value);
    }

    public function isOutsider(): bool
    {
        return $this->hasRole(Role::Outsider->value);
    }

    public function isGuruBaru(): bool
    {
        return $this->hasRole(Role::GuruBaru->value);
    }

    public function isHR(): bool
    {
        return $this->hasRole(Role::HR->value);
    }

    public function isParticipant(): bool
    {
        return $this->hasAnyRole([Role::Teacher->value, Role::Outsider->value, Role::GuruBaru->value]);
    }
}
