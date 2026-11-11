<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropForeign('feedback_question_form_id_foreign');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign('feedback_response_form_id_foreign');
            $table->dropUnique('feedback_response_form_id_question_id_user_id_unique');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('form_id', 'formID');
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->renameColumn('form_id', 'formID');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->renameColumn('form_id', 'formID');
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->foreign('formID')->references('formID')->on('feedback_form')->cascadeOnDelete();
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->foreign('formID')->references('formID')->on('feedback_form')->cascadeOnDelete();
            $table->unique(['formID', 'question_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropForeign(['formID']);
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign(['formID']);
            $table->dropUnique(['formID', 'question_id', 'user_id']);
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('formID', 'form_id');
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->renameColumn('formID', 'form_id');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->renameColumn('formID', 'form_id');
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->foreign('form_id')->references('form_id')->on('feedback_form')->cascadeOnDelete();
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->foreign('form_id')->references('form_id')->on('feedback_form')->cascadeOnDelete();
            $table->unique(['form_id', 'question_id', 'user_id']);
        });
    }
};
