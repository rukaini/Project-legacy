<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_submission')) {
            return;
        }

        Schema::create('assessment_submission', function (Blueprint $table) {
            $table->id('submission_id');
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->string('submitted_file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->json('answers_json')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('grade', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->unsignedBigInteger('graded_by_trainer_id')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('assessment_id')->on('assessment')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_submission');
    }
};
