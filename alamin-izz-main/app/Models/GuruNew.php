<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class GuruNew extends Authenticatable
{
    protected $table      = 'guru_new';
    protected $primaryKey = 'gn_id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'gn_id',
        'ic_number',
        'gn_name',
        'phone_number',
        'email',
        'password',
        'marital_status',
        'gender',
        'address',
        'race',
        'appointed_date',
        'role',
        'current_status',
        'hrid',
        'schoolID',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'appointed_date' => 'date',
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

    protected static function booted(): void
    {
        static::creating(function (GuruNew $guruNew) {
            if (! $guruNew->gn_id) {
                $guruNew->gn_id = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('guru_new')->max(DB::raw('CAST(SUBSTRING(gn_id, 3) AS UNSIGNED)'));

        return 'GN' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function hrAdministrator(): BelongsTo
    {
        return $this->belongsTo(HrAdministrator::class, 'hrid', 'hrid');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EnrollGuruBaru::class, 'gn_id', 'gn_id');
    }
}
