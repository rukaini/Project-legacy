<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('answer_value');
            $table->text('comment')->nullable()->after('rating');
            $table->timestamp('response_date')->nullable()->after('comment');
            $table->unsignedBigInteger('teacherID')->nullable()->after('response_date');
            $table->unsignedBigInteger('applicant_id')->nullable()->after('teacherID');

            $table->foreign('teacherID')->references('teacherID')->on('teacher')->nullOnDelete();
            $table->foreign('applicant_id')->references('applicant_id')->on('applicant')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedback_response', function (Blueprint $table) {
            $table->dropForeign(['teacherID']);
            $table->dropForeign(['applicant_id']);
            $table->dropColumn(['rating', 'comment', 'response_date', 'teacherID', 'applicant_id']);
        });
    }
};
