<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SSO-only users (teacher, principal, guru_new, hr_administrator) have no
 * users row; their auth identifier is a string like "canonical:teacher:T001".
 * The default sessions.user_id is a bigint, so in strict mode MySQL rejects
 * the session write and every non-outsider login bounces back to the portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Canonical identifiers can't be stored as integers.
        DB::table('sessions')->where('user_id', 'like', 'canonical:%')->delete();

        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
