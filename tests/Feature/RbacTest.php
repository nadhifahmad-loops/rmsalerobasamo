<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Matriks role dari docs/USER-FLOWS.md §6.
 * Kasir hanya memakai POS/Open Order; semua kemampuan kelola admin/owner.
 */
it('kasir tidak memiliki kemampuan kelola', function () {
    $kasir = User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'kasir'])->id]);

    foreach (['manage-users', 'manage-catalog', 'manage-tables', 'view-reports', 'manage-settings', 'void-paid'] as $ability) {
        expect(Gate::forUser($kasir)->allows($ability))->toBeFalse("kasir seharusnya tidak memiliki '{$ability}'");
    }
});

it('admin memiliki seluruh kemampuan kelola', function () {
    $admin = User::factory()->admin()->create();

    foreach (['manage-users', 'manage-catalog', 'manage-tables', 'view-reports', 'manage-settings', 'void-paid'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability))->toBeTrue("admin seharusnya memiliki '{$ability}'");
    }
});

it('owner memiliki seluruh kemampuan kelola', function () {
    $owner = User::factory()->owner()->create();

    foreach (['manage-users', 'manage-catalog', 'manage-tables', 'view-reports', 'manage-settings', 'void-paid'] as $ability) {
        expect(Gate::forUser($owner)->allows($ability))->toBeTrue("owner seharusnya memiliki '{$ability}'");
    }
});

it('menolak otorisasi untuk tamu', function () {
    expect(Gate::forUser(null)->allows('manage-users'))->toBeFalse();
});

it('menolak akses halaman terautentikasi untuk tamu (authorization negatif)', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/settings/profile')->assertRedirect('/login');
    $this->patch('/settings/profile', ['name' => 'Hacker'])->assertRedirect('/login');
    $this->put('/settings/password', ['current_password' => 'x', 'password' => 'y', 'password_confirmation' => 'y'])->assertRedirect('/login');
});

it('membagikan permissions yang benar ke frontend', function () {
    $kasir = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($kasir)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('auth.user.permissions.manage-users', false)
            ->where('auth.user.role', 'kasir'));

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('auth.user.permissions.manage-users', true)
            ->where('auth.user.role', 'admin'));
});

it('tidak membocorkan password atau remember token ke frontend', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->missing('auth.user.password')
            ->missing('auth.user.remember_token')
            ->missing('auth.user.role_id'));
});
