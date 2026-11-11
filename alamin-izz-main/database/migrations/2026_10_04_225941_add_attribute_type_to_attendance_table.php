<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance rows now come from two places:
 *  - 'training'          → linked to a course_session (session_id), as before
 *  - 'penyelaras ladap'  → linked to a penyelaras_ladap session (penyelaras_ladap_id)
 *
 * session_id becomes nullable so Penyelaras Ladap rows can leave it empty.
 * Existing rows keep attribute_type = 'training' via the default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->string('attribute_type', 30)->default('training')->after('hours_ladap');
            $table->unsignedBigInteger('penyelaras_ladap_id')->nullable()->after('session_id');
            $table->foreign('penyelaras_ladap_id')
                ->references('id')->on('penyelaras_ladap')
                ->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE attendance MODIFY session_id VARCHAR(20) NULL');
    }

    public function down(): void
    {
        DB::table('attendance')->where('attribute_type', 'penyelaras ladap')->delete();

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['penyelaras_ladap_id']);
            $table->dropColumn(['attribute_type', 'penyelaras_ladap_id']);
        });

        DB::statement('ALTER TABLE attendance MODIFY session_id VARCHAR(20) NOT NULL');
    }
};
