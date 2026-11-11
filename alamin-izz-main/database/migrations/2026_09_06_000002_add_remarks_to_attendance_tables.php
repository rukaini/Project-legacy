<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('hours_ladap');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('timestamp_status');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('hours_ladap');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};
