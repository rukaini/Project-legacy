<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_course');
            $table->dropForeign('fk_feedback_form_session');
            $table->dropIndex('idx_feedback_form_course');
            $table->dropIndex('idx_feedback_form_session');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('course_id', 'courseID');
            $table->renameColumn('session_id', 'sessionID');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->index('courseID', 'idx_feedback_form_course');
            $table->index('sessionID', 'idx_feedback_form_session');

            $table->foreign('courseID', 'fk_feedback_form_course')
                ->references('course_id')->on('course')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('sessionID', 'fk_feedback_form_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_course');
            $table->dropForeign('fk_feedback_form_session');
            $table->dropIndex('idx_feedback_form_course');
            $table->dropIndex('idx_feedback_form_session');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('courseID', 'course_id');
            $table->renameColumn('sessionID', 'session_id');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->index('course_id', 'idx_feedback_form_course');
            $table->index('session_id', 'idx_feedback_form_session');

            $table->foreign('course_id', 'fk_feedback_form_course')
                ->references('course_id')->on('course')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('session_id', 'fk_feedback_form_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
