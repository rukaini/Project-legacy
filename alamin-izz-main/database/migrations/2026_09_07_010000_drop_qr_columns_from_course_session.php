<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->dropUnique(['qr_code']);
            $table->dropColumn(['qr_code', 'qr_expiry_time']);
        });
    }

    public function down(): void
    {
        Schema::table('course_session', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->unique()->after('location');
            $table->dateTime('qr_expiry_time')->nullable()->after('qr_code');
        });
    }
};
