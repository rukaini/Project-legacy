<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_participant', function (Blueprint $table) {
            $table->string('participantID', 20)->primary();
            $table->string('participantType', 20);
            $table->string('participantName', 255);
            $table->string('organisationName', 255)->nullable();
            $table->string('ICNumber', 20)->nullable();
            $table->string('phoneNumber', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('paymentProof', 255)->nullable();
            $table->string('RSVPStatus', 30)->default('Will come');
            $table->boolean('isFeedbackCompleted')->default(false);
            $table->string('replacementStatus', 20)->default('original');
            $table->string('replacedByName', 255)->nullable();
            $table->text('replacementReason')->nullable();

            // courseID/teacherID stay bigint here — this app's real course.courseID and
            // teacher.teacherID are bigint auto-increment, unlike the varchar(20) in the
            // reference schema (same exception applied to every other table this session).
            $table->unsignedBigInteger('courseID');
            $table->string('gn_id', 20)->nullable();
            $table->unsignedBigInteger('outsider_id')->nullable();
            $table->unsignedBigInteger('teacherID')->nullable();
            // staffID is an external-system identifier (staff_edu doesn't exist locally) —
            // left unconstrained, same as approvedByStaff/createdByStaff elsewhere this session.
            $table->string('staffID', 20)->nullable();

            $table->index('courseID', 'idx_course_participant_course');
            $table->index('gn_id', 'idx_course_participant_gn');
            $table->index('outsider_id', 'idx_course_participant_outsider');
            $table->index('teacherID', 'idx_course_participant_teacher');
            $table->index('staffID', 'idx_course_participant_staff');

            $table->foreign('courseID', 'fk_course_participant_course')
                ->references('courseID')->on('course')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('gn_id', 'fk_course_participant_gn')
                ->references('gn_id')->on('guru_new')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('outsider_id', 'fk_course_participant_outsider')
                ->references('outsider_id')->on('outsider')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('teacherID', 'fk_course_participant_teacher')
                ->references('teacherID')->on('teacher')
                ->onDelete('set null')->onUpdate('cascade');
        });

        DB::statement("
            ALTER TABLE course_participant
            ADD CONSTRAINT chk_course_participant_1 CHECK (participantType in ('teacher','new_teacher','staff','public')),
            ADD CONSTRAINT chk_course_participant_2 CHECK (RSVPStatus in ('Will come','Cannot attend','Pending'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('course_participant');
    }
};
