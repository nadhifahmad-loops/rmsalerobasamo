<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the roles defined in docs/USER-FLOWS.md §6.
     */
    public function run(): void
    {
        foreach (['kasir', 'admin', 'owner'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
