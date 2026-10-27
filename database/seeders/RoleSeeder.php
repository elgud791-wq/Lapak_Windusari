<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::insert([
            ['name' => 'admin_desa', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin_kecamatan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
