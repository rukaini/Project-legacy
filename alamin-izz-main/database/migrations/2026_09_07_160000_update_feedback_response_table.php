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

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->renameColumn('response_id', 'responseID');
            $table->renameColumn('question_id', 'questionID');
            $table->renameColumn('response_date', 'responseDate');
        });

        DB::statement('ALTER TABLE feedback_response MODIFY responseDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->string('participantID', 20)->nullable()->after('user_id');
            // staffID is an external-system identifier (staff_edu doesn't exist locally) —
            // left unconstrained, same pattern as approvedByStaff/createdByStaff elsewhere.
            $table->string('staffID', 20)->nullable()->after('participantID');
            $table->string('responseType', 20)->default('participant')->after('staffID');
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->index('participantID', 'idx_feedback_response_participant');
            $table->index('staffID', 'idx_feedback_response_staff');

            $table->foreign('questionID', 'fk_feedback_response_question')
                ->references('questionID')->on('feedback_question')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('participantID', 'fk_feedback_response_participant')
                ->references('participantID')->on('course_participant')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // chk_feedback_response_3 from the reference ("responseType='participant' requires
        // participantID, or responseType='staff_edu' requires staffID") is not addable: MySQL
        // refuses a CHECK on a column that is also the child of a CASCADE foreign key (error
        // 3823), and participantID cascades. Same restriction hit earlier on feedback_form;
        // resolution is the same — keep the cascade, skip that one check.
        DB::statement("
            ALTER TABLE feedback_response
            ADD CONSTRAINT chk_feedback_response_1 CHECK (rating is null or rating between 1 and 5),
            ADD CONSTRAINT chk_feedback_response_2 CHECK (responseType in ('participant','staff_edu'))
        ");
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE feedback_response
            DROP CONSTRAINT chk_feedback_response_1,
            DROP CONSTRAINT chk_feedback_response_2
        ');

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_response_question');
            $table->dropForeign('fk_feedback_response_participant');
            $table->dropIndex('idx_feedback_response_participant');
            $table->dropIndex('idx_feedback_response_staff');
            $table->dropColumn(['participantID', 'staffID', 'responseType']);
        });

        Schema::table('feedback_response', function (Blueprint $table) {
            $table->renameColumn('responseID', 'response_id');
            $table->renameColumn('questionID', 'question_id');
            $table->renameColumn('responseDate', 'response_date');
            $table->timestamp('response_date')->nullable()->change();

            $table->foreign('question_id')->references('questionID')->on('feedback_question')->cascadeOnDelete();
        });
    }
};
