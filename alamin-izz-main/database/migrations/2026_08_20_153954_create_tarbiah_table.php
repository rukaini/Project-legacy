<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarbiah', function (Blueprint $table) {
            $table->id('tarbiah_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('schoolID');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('schoolID')->references('schoolID')->on('school')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarbiah');
    }
};
