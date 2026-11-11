<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign('feedback_response_question_id_foreign');
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->renameColumn('question_id', 'questionID');
            $table->renameColumn('question_text', 'questionText');
            $table->renameColumn('question_type', 'questionType');
            $table->renameColumn('is_required', 'isRequired');
            $table->renameColumn('question_order', 'questionOrder');
        });

        DB::statement("
            ALTER TABLE feedback_question
            MODIFY questionType enum('likert','rating','short_text','paragraph','yes_no','multiple_choice','image')
            NOT NULL
        ");

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->string('questionImage', 255)
                ->nullable()
                ->comment('Image file path when questionType is image')
                ->after('questionType');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->foreign('question_id')->references('questionID')->on('feedback_question')->cascadeOnDelete();
        });

        DB::statement("
            ALTER TABLE feedback_question
            ADD CONSTRAINT chk_feedback_question_2
            CHECK (questionImage is null or questionImage like '%.jpg' or questionImage like '%.jpeg' or questionImage like '%.png')
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE feedback_question DROP CONSTRAINT chk_feedback_question_2');

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
        });

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->dropColumn('questionImage');
        });

        DB::statement("
            ALTER TABLE feedback_question
            MODIFY questionType enum('likert','rating','short_text','paragraph','yes_no','multiple_choice')
            NOT NULL
        ");

        Schema::table('feedback_question', function (Blueprint $table) {
            $table->renameColumn('questionID', 'question_id');
            $table->renameColumn('questionText', 'question_text');
            $table->renameColumn('questionType', 'question_type');
            $table->renameColumn('isRequired', 'is_required');
            $table->renameColumn('questionOrder', 'question_order');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->foreign('question_id')->references('question_id')->on('feedback_question')->cascadeOnDelete();
        });
    }
};
