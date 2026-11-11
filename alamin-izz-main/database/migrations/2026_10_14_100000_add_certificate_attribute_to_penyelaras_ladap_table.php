<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teachers' uploaded certificates share the penyelaras_ladap table. Rows are
 * told apart by attribute_type; existing rows default to 'penyelaras_ladap'.
 * For a certificate, assigned_teacher_id is the teacher who uploaded it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyelaras_ladap', function (Blueprint $table) {
            $table->enum('attribute_type', ['penyelaras_ladap', 'certificate'])
                ->default('penyelaras_ladap')
                ->after('id');
            $table->string('certificate_path')->nullable()->after('location');
            $table->string('certificate_original_name')->nullable()->after('certificate_path');

            $table->index('attribute_type');
        });
    }

    public function down(): void
    {
        Schema::table('penyelaras_ladap', function (Blueprint $table) {
            $table->dropIndex(['attribute_type']);
            $table->dropColumn(['attribute_type', 'certificate_path', 'certificate_original_name']);
        });
    }
};
