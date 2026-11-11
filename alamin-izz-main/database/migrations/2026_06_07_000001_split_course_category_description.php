<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->enum('course_category', ['single', 'series', 'multi_day'])->nullable()->after('course_name');
            $table->text('description')->nullable()->after('course_category');
        });

        // Migrate existing data: move old combined text into description
        DB::table('course')->whereNotNull('course_category_description')->update([
            'description' => DB::raw('course_category_description'),
        ]);

        Schema::table('course', function (Blueprint $table) {
            $table->dropColumn('course_category_description');
        });
    }

    public function down(): void
    {
        Schema::table('course', function (Blueprint $table) {
            $table->text('course_category_description')->nullable()->after('course_name');
        });

        DB::table('course')->whereNotNull('description')->update([
            'course_category_description' => DB::raw('description'),
        ]);

        Schema::table('course', function (Blueprint $table) {
            $table->dropColumn(['course_category', 'description']);
        });
    }
};
