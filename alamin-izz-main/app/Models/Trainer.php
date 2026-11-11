<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Trainer extends Model
{
    protected $table      = 'trainer';
    // Real column is `trainerID` (camelCase) — must match exactly so
    // fresh()/refresh()/save()/route-model-binding key lookups work; the
    // `trainer_id` snake_case name is kept only as a virtual read/write
    // alias below, for the rest of the app's naming convention.
    protected $primaryKey = 'trainerID';
    public    $incrementing = false;
    protected $keyType    = 'string';
    public    $timestamps = false;

    protected $fillable = [
        'trainerID',
        'trainer_name',
        'trainer_ic',
        'trainer_email',
        'trainer_phoneNo',
        'expertise',
        'trainer_pic',
        'average_rating',
        'status',
    ];

    // These are virtual accessors (not real columns — see below), so
    // Eloquent won't include them in JSON output to the frontend unless
    // explicitly appended.
    protected $appends = ['trainer_id', 'trainer_name', 'trainer_ic', 'trainer_email', 'trainer_pic', 'trainer_phoneNo', 'average_rating'];

    protected static function booted(): void
    {
        static::creating(function (Trainer $trainer) {
            if (! $trainer->trainerID) {
                $trainer->trainerID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('trainer')->max(DB::raw('CAST(SUBSTRING(trainerID, 3) AS UNSIGNED)'));

        return 'TR' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // alamin_main_database uses camelCase column names for this table; the
    // rest of this app consistently uses snake_case — map each one here so
    // only this integration boundary needs to know about the discrepancy.
    public function getTrainerIdAttribute() { return $this->attributes['trainerID'] ?? null; }

    public function getTrainerNameAttribute() { return $this->attributes['trainerName'] ?? null; }
    public function setTrainerNameAttribute($value): void { $this->attributes['trainerName'] = $value; }

    public function getTrainerIcAttribute() { return $this->attributes['trainerIC'] ?? null; }
    public function setTrainerIcAttribute($value): void { $this->attributes['trainerIC'] = $value; }

    public function getTrainerEmailAttribute() { return $this->attributes['trainerEmail'] ?? null; }
    public function setTrainerEmailAttribute($value): void { $this->attributes['trainerEmail'] = $value; }

    public function getTrainerPicAttribute() { return $this->attributes['trainerPic'] ?? null; }
    public function setTrainerPicAttribute($value): void { $this->attributes['trainerPic'] = $value; }

    public function getTrainerPhoneNoAttribute() { return $this->attributes['trainerPhoneNo'] ?? null; }
    public function setTrainerPhoneNoAttribute($value): void { $this->attributes['trainerPhoneNo'] = $value; }

    public function getAverageRatingAttribute() { return $this->attributes['averageRating'] ?? null; }
    public function setAverageRatingAttribute($value): void { $this->attributes['averageRating'] = $value; }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(CourseSession::class, 'session_trainer', 'trainerID', 'sessionID', 'trainerID', 'sessionID');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'trainer_id', 'trainerID');
    }
}
