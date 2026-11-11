<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded: PSM Assessment owns the full submission contract.
    }

    public function down(): void
    {
        // Trainer does not own the external assessment_submission table.
    }
};
