<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: fyp2 owns the canonical qr_session table.
    }

    public function down(): void
    {
        // Trainer does not own the external qr_session table.
    }
};
