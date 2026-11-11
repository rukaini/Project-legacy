<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feedback_question')) {
            return;
        }

        Schema::create('feedback_question', function (Blueprint $table) {
            $table->bigIncrements('question_id');
            $table->unsignedBigInteger('form_id');
            $table->string('category', 100)->nullable();
            $table->text('question_text');
            $table->enum('question_type', ['likert', 'rating', 'short_text', 'paragraph', 'yes_no', 'multiple_choice']);
            $table->text('options_json')->nullable();
            $table->boolean('is_required')->default(true);
            $table->integer('question_order')->default(0);

            $table->foreign('form_id')->references('form_id')->on('feedback_form')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_question');
    }
};
