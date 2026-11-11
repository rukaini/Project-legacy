<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessment_question')) {
            return;
        }

        Schema::create('assessment_question', function (Blueprint $table) {
            $table->id('question_id');
            $table->unsignedBigInteger('assessment_id');
            $table->text('question_text');
            $table->string('question_image_path')->nullable();
            $table->integer('question_order')->default(1);
            $table->string('question_type', 50);
            $table->json('options_json')->nullable();
            $table->text('correct_answer')->nullable();
            $table->decimal('marks', 8, 2)->default(1);
            $table->text('grading_rubric')->nullable();
            $table->timestamps();

            $table->foreign('assessment_id')->references('assessment_id')->on('assessment')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_question');
    }
};
