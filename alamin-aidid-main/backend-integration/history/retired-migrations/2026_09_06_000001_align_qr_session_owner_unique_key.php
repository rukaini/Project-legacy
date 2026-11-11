<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: fyp2 provisions the canonical QR keys.
    }

    public function down(): void
    {
        // Trainer does not own the external QR keys.
    }
};
