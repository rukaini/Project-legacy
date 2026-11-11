<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same problem as tarbiah.created_by (see
 * 2026_09_30_122605_widen_tarbiah_created_by_for_canonical_identities.php):
 * any action performed by a Teacher/Principal/GuruBaru signed in via SSO
 * without a `users` row (App\Models\CanonicalSsoUser) fails to write an
 * audit log entry, because `user_id` is an unsignedBigInteger FK to `users`.
 * Drop the foreign key and widen the column so canonical identities' raw
 * external IDs can be stored. `AuditLog::user()` (belongsTo User) simply
 * returns null for these rows — the existing tolerance already used for
 * external actors elsewhere in this app.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_user_id_foreign');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('user_id', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
