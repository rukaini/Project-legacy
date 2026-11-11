<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outsider', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('ICNumber')->nullable()->change();
            $table->string('phoneNumber')->nullable()->change();
            $table->string('gender')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('outsider', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('ICNumber')->nullable(false)->change();
            $table->string('phoneNumber')->nullable(false)->change();
            $table->string('gender')->nullable(false)->change();
        });
    }
};
