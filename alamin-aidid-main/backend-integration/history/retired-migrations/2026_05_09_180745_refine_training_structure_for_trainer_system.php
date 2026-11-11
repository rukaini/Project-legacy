<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by the clean canonical base schema. No legacy row conversion occurs.
    }

    public function down(): void
    {
        // The canonical base migration owns the final columns.
    }
};
