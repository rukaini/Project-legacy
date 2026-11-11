<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recipient is addressed by whichever IDs identify them: the canonical
 * teacherID / principalID / gn_id / hrid (SSO users have no users row), and
 * user_id for local accounts such as outsiders. A notification belongs to a
 * user when any of those columns matches them.
 */
class Notification extends Model
{
    protected $table      = 'notification_ladap';
    protected $primaryKey = 'Notification_id';
    public    $timestamps = false;

    /** Recipient columns, in the order used to tell two recipients apart. */
    public const RECIPIENT_COLUMNS = ['teacherID', 'principalID', 'gn_id', 'hrid', 'user_id'];

    protected $fillable = [
        'user_id', 'message', 'type', 'url', 'is_read', 'created_at',
        'teacherID', 'gn_id', 'principalID', 'hrid',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Notifications addressed to this user, by any of their recipient IDs. */
    public function scopeForRecipient(Builder $query, User $user): Builder
    {
        $identity = self::identityOf($user);

        if ($identity === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($identity) {
            foreach ($identity as $column => $value) {
                $q->orWhere($column, $value);
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Recipient IDs for a user. An SSO user is identified by their canonical
     * ID only; a local user by user_id plus any canonical record sharing
     * their email.
     *
     * @return array<string, int|string>
     */
    public static function identityOf(User $user): array
    {
        if ($user instanceof CanonicalSsoUser) {
            $column = match ($user->canonical_source) {
                'teacher'          => 'teacherID',
                'principal'        => 'principalID',
                'guru_new'         => 'gn_id',
                'hr_administrator' => 'hrid',
                default            => null,
            };

            return $column ? [$column => (string) $user->external_id] : [];
        }

        $email = $user->email;

        return array_filter([
            'user_id'     => $user->id,
            'teacherID'   => $email ? Teacher::where('email', $email)->value('teacherID') : null,
            'principalID' => $email ? Principal::where('email', $email)->value('principalID') : null,
            'gn_id'       => $email ? GuruNew::where('email', $email)->value('gn_id') : null,
            'hrid'        => $email ? HrAdministrator::where('email', $email)->value('hrid') : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Send one notification. $to is a recipient identity, e.g. ['teacherID' => 'T001']. */
    public static function send(array $to, string $message, string $type, ?string $url = null): void
    {
        self::sendMany([$to], $message, $type, $url);
    }

    /**
     * Send the same notification to many recipients, one row each. The same
     * person reached twice (e.g. as a local user and as a teacher) gets one row.
     *
     * @param iterable<array<string, int|string|null>> $recipients
     */
    public static function sendMany(iterable $recipients, string $message, string $type, ?string $url = null): void
    {
        $unique = [];
        foreach ($recipients as $to) {
            $to = array_filter($to, fn ($v) => $v !== null && $v !== '');
            $key = self::recipientKey($to);
            if ($key === null) continue;

            $unique[$key] = array_merge($unique[$key] ?? [], $to);
        }

        $now  = now();
        $rows = array_map(fn (array $to) => array_merge(
            array_fill_keys(self::RECIPIENT_COLUMNS, null),
            array_intersect_key($to, array_flip(self::RECIPIENT_COLUMNS)),
            [
                'message'    => $message,
                'type'       => $type,
                'url'        => $url,
                'is_read'    => false,
                'created_at' => $now,
            ],
        ), array_values($unique));

        foreach (array_chunk($rows, 500) as $chunk) {
            static::insert($chunk);
        }
    }

    /** The column/value that identifies a recipient, or null if they have none. */
    private static function recipientKey(array $to): ?string
    {
        foreach (self::RECIPIENT_COLUMNS as $column) {
            if (isset($to[$column])) {
                return $column . ':' . $to[$column];
            }
        }

        return null;
    }
}
