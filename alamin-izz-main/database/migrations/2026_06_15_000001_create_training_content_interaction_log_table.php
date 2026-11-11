<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_content_interaction_log')) {
            return;
        }

        Schema::create('training_content_interaction_log', function (Blueprint $table) {
            $table->id('interaction_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('block_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->dateTime('first_opened_at')->nullable();
            $table->dateTime('last_accessed_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('total_seconds')->default(0);
            $table->string('last_event_type', 50)->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'block_id']);
            $table->index('block_id');
            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_content_interaction_log');
    }
};
