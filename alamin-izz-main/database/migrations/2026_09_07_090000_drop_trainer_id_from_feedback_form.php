<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('feedback_form_trainer_id_foreign');
            $table->dropColumn('trainer_id');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_form', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_id')->nullable()->after('title');
            $table->foreign('trainer_id')->references('trainer_id')->on('trainer');
        });
    }
};
