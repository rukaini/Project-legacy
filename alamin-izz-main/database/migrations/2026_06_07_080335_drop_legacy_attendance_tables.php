<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }

    public function down(): void
    {
        // Recreating these tables is intentionally not supported.
        // Restore from a database backup if needed.
    }
};
