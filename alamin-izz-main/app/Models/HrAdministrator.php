<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class HrAdministrator extends Authenticatable
{
    protected $table      = 'hr_administrator';
    protected $primaryKey = 'hrid';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'hrid',
        'username',
        'email',
        'phone_number',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getHrIDAttribute()
    {
        return $this->attributes['hrid'] ?? null;
    }

    public function setHrIDAttribute($value): void
    {
        $this->attributes['hrid'] = $value;
    }

    public function getHrnameAttribute()
    {
        return $this->attributes['username'] ?? null;
    }

    public function setHrnameAttribute($value): void
    {
        $this->attributes['username'] = $value;
    }

    public function guruNewRecords(): HasMany
    {
        return $this->hasMany(GuruNew::class, 'hrid', 'hrid');
    }
}
