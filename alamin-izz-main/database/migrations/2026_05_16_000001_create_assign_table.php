<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assign', function (Blueprint $table) {
            $table->unsignedBigInteger('schoolID');
            $table->unsignedBigInteger('teacherID');
            $table->date('assignDate');
            $table->string('status');

            $table->primary(['schoolID', 'teacherID']);
            $table->foreign('schoolID')->references('schoolID')->on('school')->cascadeOnDelete();
            $table->foreign('teacherID')->references('teacherID')->on('teacher')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assign');
    }
};
