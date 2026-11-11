<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_outsider', function (Blueprint $table) {
            $table->id('attendOutsider_id');
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->unsignedBigInteger('outsider_id');
            $table->unsignedBigInteger('session_id');
            $table->timestamps();

            $table->foreign('outsider_id')->references('outsider_id')->on('outsider')->onDelete('cascade');
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');

            $table->unique(['outsider_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_outsider');
    }
};
