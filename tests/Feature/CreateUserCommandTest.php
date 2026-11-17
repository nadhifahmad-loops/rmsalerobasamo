<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

it('membuat pengguna baru dengan role valid', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('user:create', [
        'username' => 'kasir.baru',
        'role' => 'kasir',
        '--name' => 'Kasir Baru',
        '--password' => 'rahasia-mantap',
    ])->assertSuccessful();

    $user = User::where('username', 'kasir.baru')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Kasir Baru')
        ->and($user->role->name)->toBe('kasir')
        ->and($user->is_active)->toBeTrue()
        ->and(Hash::check('rahasia-mantap', $user->password))->toBeTrue();
});

it('menolak role yang tidak dikenal', function () {
    Role::firstOrCreate(['name' => 'kasir']);

    $this->artisan('user:create', [
        'username' => 'pengguna.baru',
        'role' => 'superadmin',
        '--password' => 'rahasia-mantap',
    ])->assertFailed();

    expect(User::where('username', 'pengguna.baru')->exists())->toBeFalse();
});

it('menolak username yang sudah dipakai', function () {
    User::factory()->create(['username' => 'sudah.ada']);

    $this->artisan('user:create', [
        'username' => 'sudah.ada',
        'role' => 'kasir',
        '--password' => 'rahasia-mantap',
    ])->assertFailed();
});

it('menolak password yang lemah', function () {
    $this->artisan('user:create', [
        'username' => 'pengguna.lemah',
        'role' => 'kasir',
        '--password' => '123',
    ])->assertFailed();

    expect(User::where('username', 'pengguna.lemah')->exists())->toBeFalse();
});
