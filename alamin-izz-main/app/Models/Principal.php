<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class Principal extends Authenticatable
{
    protected $table = 'principal';
    protected $primaryKey = 'principalID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'principalID',
        'principalName',
        'ICNumber',
        'phoneNumber',
        'email',
        'martialStatus',
        'gender',
        'address',
        'race',
        'appointedDate',
        'pensionDate',
        'latestAge',
        'password',
        'schoolID',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'appointedDate' => 'date',
            // serviceDate here is years-of-service (int), auto-computed by a
            // DB trigger from appointedDate — not a literal date.
            'pensionDate'   => 'date',
            'password'      => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Principal $principal) {
            if (! $principal->principalID) {
                $principal->principalID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('principal')->max(DB::raw('CAST(SUBSTRING(principalID, 3) AS UNSIGNED)'));

        return 'PR' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database column is `maritalStatus`; the rest of this app
    // consistently uses the `martialStatus` spelling — map it here so only
    // this integration boundary needs to know about the discrepancy.
    public function getMartialStatusAttribute()
    {
        return $this->attributes['maritalStatus'] ?? null;
    }

    public function setMartialStatusAttribute($value): void
    {
        $this->attributes['maritalStatus'] = $value;
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'schoolID', 'schoolID');
    }
}
