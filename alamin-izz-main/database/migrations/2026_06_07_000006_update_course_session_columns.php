<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropColumn(['session_title', 'session_venue']);
        });

        Schema::table('course_session', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('session_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('location')->nullable()->after('end_time');
            $table->string('qr_code')->nullable()->unique()->after('location');
            $table->dateTime('qr_expiry_time')->nullable()->after('qr_code');
        });
    }

    public function down(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropUnique(['qr_code']);
            $table->dropColumn(['start_time', 'end_time', 'location', 'qr_code', 'qr_expiry_time']);
        });

        Schema::table('course_session', function (Blueprint $table) {
            $table->string('session_title')->nullable()->after('course_id');
            $table->string('session_venue')->nullable()->after('session_title');
        });
    }
};
