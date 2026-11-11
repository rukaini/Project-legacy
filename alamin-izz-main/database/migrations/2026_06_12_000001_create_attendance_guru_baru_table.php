<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_guru_baru', function (Blueprint $table) {
            $table->id('attendGuruBaru_id');
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('session_id');
            $table->timestamps();

            $table->foreign('applicant_id')->references('applicant_id')->on('applicant')->onDelete('cascade');
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');
            $table->unique(['applicant_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_guru_baru');
    }
};
