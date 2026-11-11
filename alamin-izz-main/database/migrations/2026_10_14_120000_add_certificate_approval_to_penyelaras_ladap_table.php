<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guru Besar review of teacher-uploaded certificates (attribute_type =
 * 'certificate' only). approval NULL = pending review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyelaras_ladap', function (Blueprint $table) {
            $table->enum('approval', ['approved', 'rejected'])->nullable()->after('certificate_original_name');
            $table->decimal('credit_hours', 5, 2)->nullable()->after('approval');
            $table->text('remarks')->nullable()->after('credit_hours');
            $table->string('reviewed_by', 255)->nullable()->after('remarks');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('penyelaras_ladap', function (Blueprint $table) {
            $table->dropColumn(['approval', 'credit_hours', 'remarks', 'reviewed_by', 'reviewed_at']);
        });
    }
};
