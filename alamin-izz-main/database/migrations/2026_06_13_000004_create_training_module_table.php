<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_module')) {
            return;
        }

        Schema::create('training_module', function (Blueprint $table) {
            $table->id('module_id');
            $table->unsignedBigInteger('course_id');
            $table->string('module_title');
            $table->integer('module_order')->default(1);
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_module');
    }
};
