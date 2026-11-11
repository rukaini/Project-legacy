<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school', function (Blueprint $table) {
            $table->id('schoolID');
            $table->string('schoolName');
            $table->string('schoolAddress');
            $table->date('registerDate');
            $table->string('phoneNumber');
            $table->integer('totalTeacher')->default(0);
            $table->integer('vacancy')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school');
    }
};
