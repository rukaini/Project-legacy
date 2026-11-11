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
        // 1. Rename table
        Schema::rename('notifications', 'notification_ladap');

        // 2. Rename PK id → Notification_id and add new FK columns
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->renameColumn('id', 'Notification_id');
            $table->unsignedBigInteger('teacherID')->nullable()->after('user_id');
            $table->unsignedBigInteger('applicant_id')->nullable()->after('teacherID');
            $table->unsignedBigInteger('principalID')->nullable()->after('applicant_id');

            $table->foreign('teacherID')->references('teacherID')->on('teacher')->nullOnDelete();
            $table->foreign('applicant_id')->references('applicant_id')->on('applicant')->nullOnDelete();
            $table->foreign('principalID')->references('principalID')->on('principal')->nullOnDelete();
        });

        // 3. Populate teacherID, applicant_id, principalID from the users table via email
        DB::statement("
            UPDATE notification_ladap nl
            JOIN users u ON u.id = nl.user_id
            JOIN teacher t ON t.email = u.email
            SET nl.teacherID = t.teacherID
        ");

        DB::statement("
            UPDATE notification_ladap nl
            JOIN users u ON u.id = nl.user_id
            JOIN applicant a ON a.email = u.email
            SET nl.applicant_id = a.applicant_id
        ");

        DB::statement("
            UPDATE notification_ladap nl
            JOIN users u ON u.id = nl.user_id
            JOIN principal p ON p.email = u.email
            SET nl.principalID = p.principalID
        ");
    }

    public function down(): void
    {
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->dropForeign(['teacherID']);
            $table->dropForeign(['applicant_id']);
            $table->dropForeign(['principalID']);
            $table->dropColumn(['teacherID', 'applicant_id', 'principalID']);
            $table->renameColumn('Notification_id', 'id');
        });

        Schema::rename('notification_ladap', 'notifications');
    }
};
