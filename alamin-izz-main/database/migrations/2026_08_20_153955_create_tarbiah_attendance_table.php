<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarbiah_attendance', function (Blueprint $table) {
            $table->id('tarbiah_attendance_id');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('tarbiah_id');
            $table->timestamps();

            $table->foreign('teacher_id')->references('teacherID')->on('teacher')->cascadeOnDelete();
            $table->foreign('tarbiah_id')->references('tarbiah_id')->on('tarbiah')->cascadeOnDelete();

            $table->unique(['teacher_id', 'tarbiah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarbiah_attendance');
    }
};
