<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enroll_guru_baru', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('course_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('applied_at')->useCurrent();

            $table->unique(['applicant_id', 'course_id']);
            $table->foreign('applicant_id')->references('applicant_id')->on('applicant')->onDelete('cascade');
            $table->foreign('course_id')->references('course_id')->on('course')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enroll_guru_baru');
    }
};
