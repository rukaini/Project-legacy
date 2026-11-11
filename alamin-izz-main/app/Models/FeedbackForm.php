<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class FeedbackForm extends Model
{
    protected $table        = 'feedback_form';
    protected $primaryKey   = 'formID';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    public function getRouteKeyName(): string
    {
        return 'formID';
    }

    protected $fillable = [
        'formID', 'courseID', 'title', 'createdByStaff', 'createdDate',
        'sessionID', 'feedbackType', 'generatedPdf', 'pdfGeneratedByStaff', 'pdfGeneratedAt',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeedbackForm $form) {
            if (! $form->formID) {
                $form->formID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('feedback_form')->max(DB::raw('CAST(SUBSTRING(formID, 3) AS UNSIGNED)'));

        return 'FF' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'createdDate'    => 'datetime',
            'pdfGeneratedAt' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Training::class, 'courseID', 'courseID');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class, 'sessionID', 'sessionID');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(FeedbackCategory::class, 'formID', 'formID')
                    ->orderBy('categoryOrder');
    }

    // feedback_question has no direct formID column — it only relates to a
    // form through feedback_category.
    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(
            FeedbackQuestion::class,
            FeedbackCategory::class,
            'formID',     // FK on feedback_category referencing this form
            'categoryID', // FK on feedback_question referencing feedback_category
            'formID',     // local key on this form
            'categoryID', // local key on feedback_category
        )->orderBy('questionOrder');
    }
}
