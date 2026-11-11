<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('trainer', 'status')) {
            Schema::table('trainer', function (Blueprint $table): void {
                $table->string('status', 20)->default('active')->after('averageRating');
            });
        }

        if (!Schema::hasColumn('trainer', 'deactivationRemark')) {
            Schema::table('trainer', function (Blueprint $table): void {
                $table->text('deactivationRemark')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('trainer', 'deactivationRemark')) {
            Schema::table('trainer', function (Blueprint $table): void {
                $table->dropColumn('deactivationRemark');
            });
        }
    }
};
