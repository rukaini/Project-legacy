<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('course')->where('status', 'open')->update(['status' => 'upcoming']);
        DB::table('course')->where('status', 'closed')->update(['status' => 'ongoing']);
        DB::table('course')->where('status', 'completed')->update(['status' => 'complete']);
    }

    public function down(): void
    {
        DB::table('course')->where('status', 'upcoming')->update(['status' => 'open']);
        DB::table('course')->where('status', 'ongoing')->update(['status' => 'closed']);
        DB::table('course')->where('status', 'complete')->update(['status' => 'completed']);
    }
};
