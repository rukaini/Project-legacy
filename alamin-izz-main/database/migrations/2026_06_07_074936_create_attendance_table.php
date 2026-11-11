<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id('attendance_id');
            $table->decimal('timestamp_status', 5, 2)->default(0); // total session hours attended
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('session_id');
            $table->timestamps();

            $table->foreign('teacher_id')->references('teacherID')->on('teacher')->onDelete('cascade');
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');

            $table->unique(['teacher_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
