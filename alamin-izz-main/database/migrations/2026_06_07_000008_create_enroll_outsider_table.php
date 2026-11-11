<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enroll_outsider', function (Blueprint $table) {
            $table->unsignedBigInteger('outsider_id');
            $table->unsignedBigInteger('course_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('applied_at')->useCurrent();

            $table->primary(['outsider_id', 'course_id']);

            $table->foreign('outsider_id')->references('outsider_id')->on('outsider')->cascadeOnDelete();
            $table->foreign('course_id')->references('course_id')->on('course')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enroll_outsider');
    }
};
