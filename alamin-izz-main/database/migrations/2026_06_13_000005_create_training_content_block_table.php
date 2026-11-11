<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('training_content_block')) {
            return;
        }

        Schema::create('training_content_block', function (Blueprint $table) {
            $table->id('block_id');
            $table->integer('module_id');
            $table->string('block_type', 50);
            $table->string('block_title')->nullable();
            $table->text('block_content')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->unsignedBigInteger('assessment_id')->nullable();
            $table->integer('block_order')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_content_block');
    }
};
