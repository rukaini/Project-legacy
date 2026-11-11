<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: the canonical table includes seen_by_trainer_at.
    }

    public function down(): void
    {
        // The canonical base migration owns this column.
    }
};
