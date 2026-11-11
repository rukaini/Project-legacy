<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A Guru Besar signed in via SSO without a `users` row (App\Models\CanonicalSsoUser,
 * e.g. "canonical:principal:P20") fails to create a Tarbiah session: `created_by`
 * is an unsignedBigInteger with a foreign key to `users.id`, which can neither
 * store that string nor be satisfied by an identity that has no `users` row.
 *
 * Same pattern already used for external, unconstrained identifiers in this app
 * (createdByStaff, staffID — see 2026_09_07_080000_drop_staff_fk_external_system.php):
 * drop the foreign key and widen the column so canonical identities can be stored
 * as a plain string. `Tarbiah::creator()` (belongsTo User) simply returns null for
 * these rows, which is the existing tolerance used elsewhere for external creators.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarbiah', function (Blueprint $table) {
            $table->dropForeign('fk_tarbiah_created_by');
        });

        Schema::table('tarbiah', function (Blueprint $table) {
            $table->string('created_by', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tarbiah', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        Schema::table('tarbiah', function (Blueprint $table) {
            $table->foreign('created_by', 'fk_tarbiah_created_by')
                ->references('id')->on('users')->nullOnDelete();
        });
    }
};
