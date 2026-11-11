<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyelaras_ladap', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('schoolID', 20);
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->string('assigned_teacher_id', 20);
            $table->string('created_by', 255)->nullable();
            $table->timestamps();

            $table->index('schoolID');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyelaras_ladap');
    }
};
