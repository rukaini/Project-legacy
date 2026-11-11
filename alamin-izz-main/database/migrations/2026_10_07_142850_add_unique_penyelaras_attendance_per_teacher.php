<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One attendance mark per teacher per Penyelaras Ladap session. MySQL treats
 * NULLs as distinct in a unique index, so this never affects 'training' rows
 * (penyelaras_ladap_id is always NULL for those).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->unique(['teacher_id', 'penyelaras_ladap_id'], 'uq_attendance_teacher_penyelaras');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropUnique('uq_attendance_teacher_penyelaras');
        });
    }
};
