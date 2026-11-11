<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->string('session_name')->default('')->after('course_id');
        });

        Schema::table('course_session', function (Blueprint $table) {
            $table->date('session_date')->nullable(false)->change();
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
            $table->index(['course_id', 'session_date'], 'idx_course_session_course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropIndex('idx_course_session_course');
            $table->date('session_date')->nullable()->change();
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->dropColumn('session_name');
        });
    }
};
