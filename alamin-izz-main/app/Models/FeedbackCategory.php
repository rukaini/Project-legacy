<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackCategory extends Model
{
    protected $table        = 'feedback_category';
    protected $primaryKey   = 'categoryID';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = [
        'categoryID', 'categoryName', 'formID', 'categoryOrder',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'formID', 'formID');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FeedbackQuestion::class, 'categoryID', 'categoryID');
    }

    public static function firstOrCreateForForm(int|string $formID, string $name, int $order = 1): self
    {
        $existing = static::where('formID', $formID)->where('categoryName', $name)->first();
        if ($existing) {
            return $existing;
        }

        $nextNumber = static::count() + 1;

        return static::create([
            'categoryID'    => 'FC' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT),
            'categoryName'  => $name,
            'formID'        => $formID,
            'categoryOrder' => $order,
        ]);
    }
}
