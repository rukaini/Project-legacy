<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Drop every FK pointing at course.course_id before renaming it ──────
        Schema::table('assessment', function (Blueprint $table) {
            $table->dropForeign('assessment_course_id_foreign');
        });
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropForeign('course_session_course_id_foreign');
        });
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->dropForeign('enroll_guru_baru_course_id_foreign');
        });
        Schema::table('enroll_outsider', function (Blueprint $table) {
            $table->dropForeign('enroll_outsider_course_id_foreign');
        });
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->dropForeign('enroll_teacher_course_id_foreign');
        });
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_course');
        });

        // ── 2. Rename course.course_id -> courseID (stays bigint auto-increment PK) ──
        Schema::table('course', function (Blueprint $table) {
            $table->renameColumn('course_id', 'courseID');
        });

        // ── 3. Rename the FK-copy columns on every dependent table ────────────────
        Schema::table('assessment', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('course_session', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('enroll_guru_baru', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('enroll_outsider', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('enroll_teacher', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('learning_material', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('training_module', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('training_access_log', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));
        Schema::table('training_content_interaction_log', fn (Blueprint $t) => $t->renameColumn('course_id', 'courseID'));

        // ── 4. Re-add FKs against course(courseID) ─────────────────────────────────
        Schema::table('assessment', function (Blueprint $table) {
            $table->foreign('courseID', 'assessment_course_id_foreign')->references('courseID')->on('course')->cascadeOnDelete();
        });
        Schema::table('course_session', function (Blueprint $table) {
            $table->foreign('courseID', 'course_session_course_id_foreign')->references('courseID')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->foreign('courseID', 'enroll_guru_baru_course_id_foreign')->references('courseID')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_outsider', function (Blueprint $table) {
            $table->foreign('courseID', 'enroll_outsider_course_id_foreign')->references('courseID')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->foreign('courseID', 'enroll_teacher_course_id_foreign')->references('courseID')->on('course')->cascadeOnDelete();
        });
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->foreign('courseID', 'fk_feedback_form_course')
                ->references('courseID')->on('course')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // ── 5. Data normalization on course before adding CHECK constraints ───────
        DB::table('course')->where('status', 'complete')->update(['status' => 'completed']);
        DB::table('course')->where('course_mod', 'Physical')->update(['course_mod' => 'physical']);
        DB::table('course')->where('course_mod', 'Hybrid')->update(['course_mod' => 'hybrid']);
        DB::table('course')->where('course_mod', 'Online')->update(['course_mod' => 'online']);

        // ── 6. Column renames + new columns on course ──────────────────────────────
        Schema::table('course', function (Blueprint $table) {
            $table->renameColumn('course_name', 'courseName');
            $table->renameColumn('whatsapp_group', 'whatsappGroup');
            $table->renameColumn('organiser_name', 'organiserName');
            $table->renameColumn('course_mod', 'mode');
        });

        Schema::table('course', function (Blueprint $table) {
            $table->string('mode', 20)->nullable()->change();
        });

        Schema::table('course', function (Blueprint $table) {
            $table->string('courseCategory', 100)
                ->nullable()
                ->comment('Free-written type/category of course, e.g. Technology, Leadership, Tarbiah')
                ->after('course_category');
            $table->set('targetAudience', ['teacher', 'new_teacher', 'staff', 'public', 'other'])
                ->default('teacher')
                ->comment('Multi-select target audience stored in course table')
                ->after('courseCategory');
            $table->string('otherTargetAudience', 255)
                ->nullable()
                ->comment('Required when targetAudience contains other')
                ->after('targetAudience');
            $table->text('staffAttendeeIDs')
                ->nullable()
                ->comment('Comma-separated staff_edu.staffID values for Staff EDU who will attend this course')
                ->after('otherTargetAudience');
            $table->string('staffAttendeeAssignedBy', 20)->nullable()->after('staffAttendeeIDs');
            $table->date('staffAttendeeAssignedDate')->nullable()->after('staffAttendeeAssignedBy');
            $table->decimal('courseRating', 3, 2)->default(0.00)->after('staffAttendeeAssignedDate');
            $table->string('onlineLink', 255)->nullable()->after('mode');
            $table->date('closeDate')->nullable()->after('whatsappGroup');
            $table->string('courseType', 20)->nullable()->after('organiserName');
        });

        Schema::table('course', function (Blueprint $table) {
            $table->string('status', 20)->default('upcoming')->change();

            $table->index('targetAudience', 'idx_course_targetAudience');
            $table->index('staffAttendeeAssignedBy', 'idx_course_attendee_assigned_by');
        });

        // ── 7. CHECK constraints (all verified against real data first) ───────────
        DB::statement("
            ALTER TABLE course
            ADD CONSTRAINT chk_course_1 CHECK (capacity > 0),
            ADD CONSTRAINT chk_course_2 CHECK (price is null or price >= 0),
            ADD CONSTRAINT chk_course_3 CHECK (courseRating between 0 and 5),
            ADD CONSTRAINT chk_course_4 CHECK (status in ('upcoming','ongoing','completed','cancelled')),
            ADD CONSTRAINT chk_course_5 CHECK (mode is null or mode in ('online','physical','hybrid')),
            ADD CONSTRAINT chk_course_6 CHECK (courseType is null or courseType in ('LMS','Simple')),
            ADD CONSTRAINT chk_course_7 CHECK (poster is null or poster like '%.jpg' or poster like '%.jpeg' or poster like '%.png' or poster like '%.pdf')
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE course
            DROP CONSTRAINT chk_course_1,
            DROP CONSTRAINT chk_course_2,
            DROP CONSTRAINT chk_course_3,
            DROP CONSTRAINT chk_course_4,
            DROP CONSTRAINT chk_course_5,
            DROP CONSTRAINT chk_course_6,
            DROP CONSTRAINT chk_course_7
        ");

        Schema::table('course', function (Blueprint $table) {
            $table->dropIndex('idx_course_targetAudience');
            $table->dropIndex('idx_course_attendee_assigned_by');
            $table->dropColumn([
                'courseCategory', 'targetAudience', 'otherTargetAudience', 'staffAttendeeIDs',
                'staffAttendeeAssignedBy', 'staffAttendeeAssignedDate', 'courseRating',
                'onlineLink', 'closeDate', 'courseType',
            ]);
            $table->string('status', 255)->default('open')->change();
        });

        Schema::table('course', function (Blueprint $table) {
            $table->renameColumn('courseName', 'course_name');
            $table->renameColumn('whatsappGroup', 'whatsapp_group');
            $table->renameColumn('organiserName', 'organiser_name');
            $table->renameColumn('mode', 'course_mod');
        });

        Schema::table('assessment', function (Blueprint $table) {
            $table->dropForeign('assessment_course_id_foreign');
        });
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropForeign('course_session_course_id_foreign');
        });
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->dropForeign('enroll_guru_baru_course_id_foreign');
        });
        Schema::table('enroll_outsider', function (Blueprint $table) {
            $table->dropForeign('enroll_outsider_course_id_foreign');
        });
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->dropForeign('enroll_teacher_course_id_foreign');
        });
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_course');
        });

        Schema::table('course', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));

        Schema::table('assessment', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('course_session', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('enroll_guru_baru', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('enroll_outsider', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('enroll_teacher', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('learning_material', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('training_module', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('training_access_log', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));
        Schema::table('training_content_interaction_log', fn (Blueprint $t) => $t->renameColumn('courseID', 'course_id'));

        Schema::table('assessment', function (Blueprint $table) {
            $table->foreign('course_id', 'assessment_course_id_foreign')->references('course_id')->on('course')->cascadeOnDelete();
        });
        Schema::table('course_session', function (Blueprint $table) {
            $table->foreign('course_id', 'course_session_course_id_foreign')->references('course_id')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_guru_baru', function (Blueprint $table) {
            $table->foreign('course_id', 'enroll_guru_baru_course_id_foreign')->references('course_id')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_outsider', function (Blueprint $table) {
            $table->foreign('course_id', 'enroll_outsider_course_id_foreign')->references('course_id')->on('course')->cascadeOnDelete();
        });
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->foreign('course_id', 'enroll_teacher_course_id_foreign')->references('course_id')->on('course')->cascadeOnDelete();
        });
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->foreign('course_id', 'fk_feedback_form_course')->references('course_id')->on('course')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
