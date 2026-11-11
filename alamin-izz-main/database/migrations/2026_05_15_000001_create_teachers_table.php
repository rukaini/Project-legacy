<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher', function (Blueprint $table) {
            $table->id('teacherID');
            $table->string('teacherName');
            $table->string('ICNumber')->unique();
            $table->string('phoneNumber');
            $table->string('email')->unique();
            $table->string('martialStatus');
            $table->string('gender');
            $table->text('address');
            $table->string('race');
            $table->date('appointedDate');
            $table->date('serviceDate');
            $table->date('pensionDate')->nullable();
            $table->integer('latestAge');
            $table->unsignedBigInteger('schoolID');
            $table->string('password');
            $table->timestamps();

            $table->foreign('schoolID')->references('schoolID')->on('school')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher');
    }
};
