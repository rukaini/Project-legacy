# Participant material access integration contract

This contract is for the EduTrain participant-facing application. Aidid / the
Trainer Management System owns `participant_material_access`; only Aidid Staff
EDU workflows may write it. EduTrain may read it to enforce participant access.

## Identity and schema

`participant_material_access.course_id` maps exactly to `course.courseID`.
The primary key guarantees at most one access row for each course.
This is a physical database foreign key with `ON UPDATE CASCADE` and
`ON DELETE CASCADE`. Aidid also validates that the course exists before it
saves a setting so the Staff EDU workflow returns a user-friendly response.

| Column | MySQL type | Null | Meaning |
|---|---|---|---|
| `course_id` | `VARCHAR(20)` | no | Course identity; primary key and physical foreign key to `course.courseID` |
| `is_enabled` | `TINYINT(1)` | no | Staff EDU configuration; defaults to `0` |
| `access_until` | `DATETIME` | yes | Inclusive expiry, or `NULL` for no expiry |
| `created_at` | `TIMESTAMP` | yes | Laravel creation timestamp |
| `updated_at` | `TIMESTAMP` | yes | Laravel update timestamp |

If no row is returned for a course, access is disabled. Consumers must fail
closed and must not infer access from the existence of material rows.

## Effective access rule

| Stored state | Effective result |
|---|---|
| `is_enabled = 0` | DENY |
| `is_enabled = 1`, `access_until IS NULL` | ALLOW indefinitely |
| `is_enabled = 1`, `access_until >= current agreed time` | ALLOW |
| `is_enabled = 1`, `access_until < current agreed time` | DENY as expired |

```text
allowed = row exists
    AND is_enabled = 1
    AND (access_until IS NULL OR access_until >= CURRENT_TIMESTAMP)
```

Aidid uses Laravel `config('app.timezone')`, currently
`Asia/Kuala_Lumpur`. The database connection has no separate timezone override.
`access_until` is a timezone-naive MySQL `DATETIME` containing Kuala Lumpur
local wall-clock time. EduTrain must compare it using the same
`Asia/Kuala_Lumpur` clock (or explicitly convert both sides to an agreed common
instant). Equality is allowed; expiry begins only when `access_until < now`.

## Read-only SQL examples

```sql
SELECT course_id, is_enabled, access_until
FROM participant_material_access
WHERE course_id = ?;
```

No returned row means DENY. If the database session's `CURRENT_TIMESTAMP`
represents `Asia/Kuala_Lumpur`, effective state may be selected as:

```sql
SELECT CASE
    WHEN is_enabled = 1
     AND (access_until IS NULL OR access_until >= CURRENT_TIMESTAMP)
    THEN 1 ELSE 0
END AS material_access_allowed
FROM participant_material_access
WHERE course_id = ?;
```

The caller must still treat no returned row as `material_access_allowed = 0`.

## Illustrative rows

These IDs are examples only and are not production identifiers.

| Example | `course_id` | `is_enabled` | `access_until` | Result at `2027-01-01 10:00:00` Kuala Lumpur time |
|---|---|---:|---|---|
| Disabled | `EXAMPLE-OFF` | 0 | `NULL` | DENY |
| Enabled forever | `EXAMPLE-FOREVER` | 1 | `NULL` | ALLOW |
| Enabled until date | `EXAMPLE-FUTURE` | 1 | `2027-12-31 23:59:00` | ALLOW |
| Expired | `EXAMPLE-EXPIRED` | 1 | `2026-12-31 23:59:00` | DENY |

## Responsibility boundary

Aidid owns the setting, the Staff EDU editing workflow, learning-material
metadata, content authoring, and the `learning_material`, `training_module`, and
`training_content_block` records. Aidid does not currently authenticate
participants or enforce participant-facing material access.

EduTrain authenticates participants, verifies that a participant legitimately
belongs to the requested course under its canonical enrollment rules, reads
this setting, and applies the result consistently to every participant-facing
page, API, preview, stream, and download endpoint.

## Existing public-storage caveat

Aidid currently stores material files on Laravel's public disk, including paths
under `course-materials/`, `training-materials/`, and `training-media/`. A
public storage link can expose those files through Aidid `/storage/...` URLs.
Hiding a link or blocking an EduTrain page does not cryptographically protect
an already-known public Aidid storage URL. Protecting or proxying those files is
a separate cross-application storage/security change; this setting alone does
not solve that concern.
