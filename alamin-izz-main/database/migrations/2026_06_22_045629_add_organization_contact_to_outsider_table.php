<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outsider', function (Blueprint $table) {
            $table->string('organization')->nullable()->after('gender');
            $table->string('contact_no')->nullable()->after('organization');
        });
    }

    public function down(): void
    {
        Schema::table('outsider', function (Blueprint $table) {
            $table->dropColumn(['organization', 'contact_no']);
        });
    }
};
