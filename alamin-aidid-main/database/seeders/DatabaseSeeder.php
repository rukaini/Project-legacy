<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The shared deployment starts empty. External owners provision their
        // own tables and data, so Trainer must not import legacy sample rows.
    }
}
