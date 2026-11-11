<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class HrAdministrator extends Authenticatable
{
    use Notifiable;

    /**
     * Table name.
     */
    protected $table = 'hr_administrator';

    /**
     * Primary key — string, not auto-incrementing.
     */
    protected $primaryKey = 'hrid';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Timestamps: the table HAS created_at / updated_at, so enable them.
     */
    public $timestamps = true;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'hrid',
        'username',
        'email',
        'password',
        'role',
        'password_change_required',
        'phone_number',
    ];

    /**
     * Hidden from JSON / array output.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     */
    protected $casts = [
        'password_change_required' => 'boolean',
        'created_at'               => 'datetime',   // ← fixes the blade crash
        'updated_at'               => 'datetime',   // ← for symmetry
        'password'                 => 'hashed',     // remove if Laravel < 10
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        // Only works if a `status` column exists — guard against missing column.
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', 'active');
        }
        return $query;
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Initial used for the header avatar (e.g. "J" for "jdoe").
     */
    public function getInitialsAttribute(): string
    {
        $source = $this->username ?: $this->email ?: 'H';
        return strtoupper(substr($source, 0, 1));
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this account must change its password on next login.
     */
    public function mustChangePassword(): bool
    {
        return (bool) ($this->password_change_required ?? false);
    }

    /**
     * Check if this account has the HR role.
     */
    public function isHr(): bool
    {
        return in_array(strtolower((string) $this->role), ['hr', 'admin'], true);
    }
}