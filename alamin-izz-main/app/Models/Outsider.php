<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outsider extends Model
{
    protected $table = 'outsider';
    protected $primaryKey = 'outsider_id';

    protected $fillable = [
        'name',
        'ICNumber',
        'phoneNumber',
        'gender',
        'organization',
        'contact_no',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EnrollOutsider::class, 'outsider_id', 'outsider_id');
    }
}
