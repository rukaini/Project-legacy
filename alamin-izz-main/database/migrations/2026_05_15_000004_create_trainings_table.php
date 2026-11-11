<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course', function (Blueprint $table) {
            $table->id('course_id');
            $table->string('course_name');
            $table->text('course_category_description')->nullable();
            $table->integer('capacity');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('open');
            $table->string('poster')->nullable();
            $table->string('course_mod')->nullable();
            $table->string('course_pic')->nullable();
            $table->string('whatsapp_group')->nullable();
            $table->string('organiser_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course');
    }
};
