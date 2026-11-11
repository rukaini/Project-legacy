<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('learning_material')) {
            return;
        }

        Schema::create('learning_material', function (Blueprint $table) {
            $table->id('material_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('trainer_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('original_file_name')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('material_type', 50)->nullable();
            $table->boolean('metadata_cleared')->default(false);
            $table->dateTime('uploaded_at')->useCurrent();

            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material');
    }
};
