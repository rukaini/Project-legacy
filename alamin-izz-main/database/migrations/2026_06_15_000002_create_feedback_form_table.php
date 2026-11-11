<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feedback_form')) {
            return;
        }

        Schema::create('feedback_form', function (Blueprint $table) {
            $table->bigIncrements('form_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('trainer_id');
            $table->string('title');
            $table->unsignedBigInteger('created_by_staff');
            $table->timestamp('created_date')->nullable();

            $table->foreign('course_id')->references('course_id')->on('course');
            $table->foreign('trainer_id')->references('trainer_id')->on('trainer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_form');
    }
};
