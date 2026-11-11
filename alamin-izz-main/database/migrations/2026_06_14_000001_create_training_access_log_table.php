<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_access_log')) {
            return;
        }

        Schema::create('training_access_log', function (Blueprint $table) {
            $table->id('access_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->timestamp('accessed_at')->useCurrent();
            $table->timestamps();

            $table->index(['course_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_access_log');
    }
};
