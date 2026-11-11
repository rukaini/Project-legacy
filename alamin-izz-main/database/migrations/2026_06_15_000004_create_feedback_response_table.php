<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_response', function (Blueprint $table) {
            $table->bigIncrements('response_id');
            $table->unsignedBigInteger('form_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id');
            $table->text('answer_value');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('form_id')->references('form_id')->on('feedback_form')->cascadeOnDelete();
            $table->foreign('question_id')->references('question_id')->on('feedback_question')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['form_id', 'question_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_response');
    }
};
