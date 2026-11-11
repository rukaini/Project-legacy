<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment')) {
            return;
        }

        Schema::create('assessment', function (Blueprint $table) {
            $table->id('assessment_id');
            $table->unsignedBigInteger('course_id');
            $table->string('type', 50);
            $table->string('title');
            $table->string('status', 50)->default('Active');
            $table->unsignedBigInteger('module_id')->nullable();
            $table->text('instruction_text')->nullable();
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->string('accepted_format', 100)->nullable();
            $table->integer('time_limit_minutes')->nullable();
            $table->integer('max_attempts')->nullable();
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->decimal('weight_percent', 5, 2)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->foreign('course_id')->references('course_id')->on('course')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment');
    }
};
