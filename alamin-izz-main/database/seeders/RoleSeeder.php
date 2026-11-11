<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        SpatieRole::firstOrCreate(['name' => Role::GuruBesar->value, 'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::Teacher->value,   'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::Outsider->value,  'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::GuruBaru->value,  'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::HR->value,        'guard_name' => 'web']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
