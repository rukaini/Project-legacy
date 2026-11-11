<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->unsignedBigInteger('schoolID')->nullable()->after('organiser_name');
            $table->foreign('schoolID')->references('schoolID')->on('school')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->dropForeign(['schoolID']);
            $table->dropColumn('schoolID');
        });
    }
};
