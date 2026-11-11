<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Relax nullability on the existing columns the new design allows to be optional.
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('feedback_form_course_id_foreign');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()->change();
            $table->unsignedBigInteger('trainer_id')->nullable()->change();
        });

        // 2. Rename + retype created_by_staff -> createdByStaff (varchar FK to hr_administrator).
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->string('createdByStaff', 20)->nullable()->after('created_by_staff');
        });

        DB::table('feedback_form')->update(['createdByStaff' => 'HR001']);

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropColumn('created_by_staff');
            $table->string('createdByStaff', 20)->nullable(false)->change();
        });

        // 3. Rename created_date -> createdDate and tighten to NOT NULL DEFAULT CURRENT_TIMESTAMP.
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('created_date', 'createdDate');
        });

        DB::table('feedback_form')->whereNull('createdDate')->update(['createdDate' => now()]);

        DB::statement('ALTER TABLE feedback_form MODIFY createdDate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        // 4. Add session-level targeting, feedback type, and PDF-generation tracking.
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')->nullable()->after('course_id');
            $table->string('feedbackType', 20)->default('participant')->after('session_id');
            $table->string('generatedPdf', 255)->nullable()->comment('Generated PDF report file path for all feedback responses')->after('feedbackType');
            $table->string('pdfGeneratedByStaff', 20)->nullable()->after('generatedPdf');
            $table->dateTime('pdfGeneratedAt')->nullable()->after('pdfGeneratedByStaff');
        });

        // 5. Indexes + constraints matching the reference names.
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->index('createdByStaff', 'idx_feedback_form_staff');
            $table->index('session_id', 'idx_feedback_form_session');
            $table->index('course_id', 'idx_feedback_form_course');
            $table->index('pdfGeneratedByStaff', 'idx_feedback_form_pdf_staff');

            $table->foreign('course_id', 'fk_feedback_form_course')
                ->references('course_id')->on('course')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('session_id', 'fk_feedback_form_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('createdByStaff', 'fk_feedback_form_staff')
                ->references('hrID')->on('hr_administrator')
                ->onUpdate('cascade');

            $table->foreign('pdfGeneratedByStaff', 'fk_feedback_form_pdf_staff')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Note: chk_feedback_form_2 ("session_id is not null or course_id is not null") from the
        // reference schema is not possible here — MySQL refuses a CHECK on a column that is also
        // the child of a CASCADE/SET NULL foreign key (error 3823), and both session_id and
        // course_id cascade on delete. That rule is enforced in application code instead.
        DB::statement("
            ALTER TABLE feedback_form
            ADD CONSTRAINT chk_feedback_form_1 CHECK (feedbackType in ('participant','staff_edu')),
            ADD CONSTRAINT chk_feedback_form_3 CHECK (generatedPdf is null or generatedPdf like '%.pdf')
        ");
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE feedback_form
            DROP CONSTRAINT chk_feedback_form_1,
            DROP CONSTRAINT chk_feedback_form_3
        ');

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_course');
            $table->dropForeign('fk_feedback_form_session');
            $table->dropForeign('fk_feedback_form_staff');
            $table->dropForeign('fk_feedback_form_pdf_staff');
            $table->dropIndex('idx_feedback_form_staff');
            $table->dropIndex('idx_feedback_form_session');
            $table->dropIndex('idx_feedback_form_course');
            $table->dropIndex('idx_feedback_form_pdf_staff');
            $table->dropColumn(['session_id', 'feedbackType', 'generatedPdf', 'pdfGeneratedByStaff', 'pdfGeneratedAt']);
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->renameColumn('createdDate', 'created_date');
            $table->timestamp('created_date')->nullable()->change();
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_staff')->nullable()->after('title');
        });

        DB::statement('UPDATE feedback_form SET created_by_staff = 1');

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropColumn('createdByStaff');
            $table->unsignedBigInteger('created_by_staff')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->unsignedBigInteger('trainer_id')->nullable(false)->change();

            $table->foreign('course_id')->references('course_id')->on('course');
        });
    }
};
