<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_material_access', function (Blueprint $table): void {
            $table->string('course_id', 20)->primary();
            $table->boolean('is_enabled')->default(false);
            $table->dateTime('access_until')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                ->references('courseID')
                ->on('course')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_material_access');
    }
};
