<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer', function (Blueprint $table) {
            $table->id('trainer_id');
            $table->string('trainer_name');
            $table->string('trainer_ic');
            $table->string('trainer_email');
            $table->string('trainer_phoneNo');
            $table->text('expertise');
            $table->string('trainer_pic')->nullable();
            $table->string('invitation_letter')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer');
    }
};
