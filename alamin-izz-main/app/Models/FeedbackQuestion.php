<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FeedbackQuestion extends Model
{
    protected $table        = 'feedback_question';
    protected $primaryKey   = 'questionID';
    public    $incrementing = false;
    protected $keyType      = 'string';
    public    $timestamps   = false;

    protected $fillable = [
        'questionID', 'categoryID', 'questionText', 'questionType', 'questionImage',
        'isRequired', 'questionOrder',
    ];

    protected $casts = [
        'isRequired' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeedbackQuestion $question) {
            if (! $question->questionID) {
                $question->questionID = self::nextId();
            }
        });
    }

    private static function nextId(): string
    {
        $max = DB::table('feedback_question')->max(DB::raw('CAST(SUBSTRING(questionID, 3) AS UNSIGNED)'));

        return 'FQ' . str_pad((string) (($max ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeedbackCategory::class, 'categoryID', 'categoryID');
    }
}
