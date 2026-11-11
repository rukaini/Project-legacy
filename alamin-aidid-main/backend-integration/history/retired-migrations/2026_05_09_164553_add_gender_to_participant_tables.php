<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: canonical participant definitions include owner fields.
    }

    public function down(): void
    {
        // The canonical base migration owns those columns.
    }
};
