<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {username : Username unik untuk login}
                            {role : Role pengguna: kasir, admin, atau owner}
                            {--name= : Nama lengkap}
                            {--email= : Email (opsional)}
                            {--password= : Password (jika kosong akan diprompt)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat akun pengguna baru (registrasi publik tidak tersedia)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = strtolower(trim($this->argument('username')));
        $roleName = strtolower(trim($this->argument('role')));

        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error("Role '{$roleName}' tidak ditemukan. Jalankan: php artisan db:seed --class=RoleSeeder");

            return self::FAILURE;
        }

        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' sudah dipakai.");

            return self::FAILURE;
        }

        $email = $this->option('email') ?: null;

        if ($email !== null && User::where('email', $email)->exists()) {
            $this->error("Email '{$email}' sudah dipakai.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password');

        try {
            validator(['password' => $password], ['password' => ['required', Password::defaults()]])->validate();
        } catch (ValidationException $e) {
            $this->error('Password tidak valid: '.collect($e->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->option('name') ?: $username,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->info("Pengguna '{$user->username}' (role: {$role->name}) berhasil dibuat.");

        return self::SUCCESS;
    }
}
