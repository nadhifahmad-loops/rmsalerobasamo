<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('pos.seed_users_enabled')) {
            $this->command?->warn(
                'User seeding disabled. Set POS_SEED_USERS=true in .env.'
            );

            return;
        }

        foreach (config('pos.user_seed_accounts', []) as $account) {
            $password = $account['password'] ?? null;

            if (! is_string($password) || strlen($password) < 12) {
                throw new RuntimeException(
                    "Password untuk role {$account['role']} harus diisi "
                    .'dan minimal 12 karakter melalui file .env.'
                );
            }

            $role = Role::where('name', $account['role'])->firstOrFail();

            $user = User::firstOrCreate(
                [
                    'username' => $account['username'],
                ],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $password,
                    'role_id' => $role->id,
                    'is_active' => true,
                ]
            );

            $status = $user->wasRecentlyCreated
                ? 'Created'
                : 'Already exists';

            $this->command?->info(
                "{$status}: {$account['username']} ({$account['role']})"
            );
        }
    }
}
