<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SSO teachers, principals, Guru Baru and HR have no users row, so a
 * notification must be addressable by their canonical ID alone:
 * user_id becomes optional and HR gets its own recipient column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('hrid', 20)->nullable()->after('principalID');

            $table->foreign('hrid', 'notification_ladap_hrid_foreign')
                ->references('hrid')
                ->on('hr_administrator')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notification_ladap', function (Blueprint $table) {
            $table->dropForeign('notification_ladap_hrid_foreign');
            $table->dropColumn('hrid');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
