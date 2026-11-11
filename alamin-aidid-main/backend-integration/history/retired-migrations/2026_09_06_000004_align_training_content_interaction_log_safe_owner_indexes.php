<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: PSM Assessment owns the interaction table and indexes.
    }

    public function down(): void
    {
        // Trainer does not own the external interaction table.
    }
};
