<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant', function (Blueprint $table) {
            $table->id('applicant_id');
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant');
    }
};
