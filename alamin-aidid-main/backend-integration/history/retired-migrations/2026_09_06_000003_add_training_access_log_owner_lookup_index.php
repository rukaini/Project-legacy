<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: PSM Assessment owns and provisions this lookup index.
    }

    public function down(): void
    {
        // Trainer does not own the external training_access_log index.
    }
};
