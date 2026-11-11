<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_session', function (Blueprint $table) {
            $table->id('session_id');
            $table->unsignedBigInteger('course_id');
            $table->string('session_title')->nullable();
            $table->date('session_date')->nullable();
            $table->string('session_venue')->nullable();
            $table->timestamps();

            $table->foreign('course_id')->references('course_id')->on('course')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_session');
    }
};
