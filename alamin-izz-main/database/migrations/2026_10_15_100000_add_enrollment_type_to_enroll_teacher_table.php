<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records how a teacher got onto a training: self-applied, or assigned by the
 * Guru Besar. Assigned teachers on an external course earn LADAP hours at 1.0x
 * instead of 0.5x. Existing rows default to 'applied' — earlier assignments were
 * never recorded as such, so they can't be told apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->enum('enrollment_type', ['applied', 'assigned'])->default('applied')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('enroll_teacher', function (Blueprint $table) {
            $table->dropColumn('enrollment_type');
        });
    }
};
