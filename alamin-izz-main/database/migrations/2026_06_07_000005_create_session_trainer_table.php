<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_trainer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('trainer_id');
            $table->timestamps();

            $table->foreign('session_id')->references('session_id')->on('course_session')->cascadeOnDelete();
            $table->foreign('trainer_id')->references('trainer_id')->on('trainer')->cascadeOnDelete();

            $table->unique(['session_id', 'trainer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_trainer');
    }
};
