<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `end_date` supports multi-day Tarbiah programmes (nullable — a single-day
 * session simply omits it, and callers treat it as equal to `session_date`).
 * `credit_hours` is the authoritative LADAP value going forward, set
 * explicitly by Guru Besar at registration instead of being derived from
 * start/end time — existing rows are left null and TarbiahService::sessionHours()
 * falls back to the old time-diff calculation for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarbiah', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('session_date');
            $table->decimal('credit_hours', 4, 2)->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('tarbiah', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'credit_hours']);
        });
    }
};
