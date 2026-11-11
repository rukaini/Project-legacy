<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: teaching_plans is provisioned by the canonical base migration.
    }

    public function down(): void
    {
        // The canonical base migration owns this table.
    }
};
