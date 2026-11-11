<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSO logs in canonical Teacher/Principal/GuruBaru identities
 * (App\Models\CanonicalSsoUser) whose auth ID is a string like
 * "canonical:principal:P20", not a bigint. The framework-default
 * `sessions.user_id` column is bigint unsigned, so writing that ID
 * fails and the login silently doesn't persist (cookie is sent, but
 * the session row's auth data never saves) — the browser bounces
 * back to guest on the very next request. Widen the column so
 * both real integer user IDs and canonical string IDs fit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('user_id', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }
};
