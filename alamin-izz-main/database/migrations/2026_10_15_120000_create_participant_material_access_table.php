<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-course switch for whether participants (teacher, guru baru, outsider)
 * can open the training from My Applications / My Trainings, optionally
 * until a cut-off date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_material_access', function (Blueprint $table) {
            $table->string('course_id', 20)->primary();
            $table->boolean('is_enabled')->default(false);
            $table->dateTime('access_until')->nullable();
            $table->timestamps();

            $table->foreign('course_id', 'participant_material_access_course_id_foreign')
                ->references('courseID')
                ->on('course')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_material_access');
    }
};
