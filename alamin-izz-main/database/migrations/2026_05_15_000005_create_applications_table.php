<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enroll_teacher', function (Blueprint $table) {
            $table->unsignedBigInteger('teacherID');
            $table->unsignedBigInteger('course_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->primary(['teacherID', 'course_id']);
            $table->foreign('teacherID')->references('teacherID')->on('teacher')->cascadeOnDelete();
            $table->foreign('course_id')->references('course_id')->on('course')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enroll_teacher');
    }
};
