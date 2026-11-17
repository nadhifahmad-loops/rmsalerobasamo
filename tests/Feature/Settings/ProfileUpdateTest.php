<?php

use App\Models\User;

it('menampilkan halaman profil untuk pengguna terautentikasi', function () {
    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/profile'));
});

it('mengizinkan pengguna memperbarui nama dan email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Kasir Baru',
            'email' => 'kasir.baru@example.com',
        ])
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->name)->toBe('Kasir Baru')
        ->and($user->email)->toBe('kasir.baru@example.com');
});

it('mengizinkan email dikosongkan (opsional)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'email' => '',
        ])
        ->assertRedirect('/settings/profile');

    expect($user->refresh()->email)->toBeNull();
});

it('menolak nama kosong', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/profile')
        ->patch('/settings/profile', ['name' => '', 'email' => $user->email])
        ->assertRedirect('/settings/profile')
        ->assertSessionHasErrors('name');
});

it('menolak email duplikat milik pengguna lain', function () {
    User::factory()->create(['email' => 'digunakan@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/profile')
        ->patch('/settings/profile', ['name' => $user->name, 'email' => 'digunakan@example.com'])
        ->assertRedirect('/settings/profile')
        ->assertSessionHasErrors('email');
});

it('mengizinkan email sendiri tetap sama tanpa error', function () {
    $user = User::factory()->create(['email' => 'miliksendiri@example.com']);

    $this->actingAs($user)
        ->patch('/settings/profile', ['name' => $user->name, 'email' => 'miliksendiri@example.com'])
        ->assertRedirect('/settings/profile')
        ->assertSessionHasNoErrors();
});

it('tidak mengizinkan tamu memperbarui profil', function () {
    $this->patch('/settings/profile', ['name' => 'Hacker'])
        ->assertRedirect('/login');
});

it('tidak menyediakan endpoint hapus akun', function () {
    $user = User::factory()->create();

    // URI hanya didaftarkan untuk PATCH; DELETE tidak diizinkan (405)
    // sehingga tidak ada endpoint hapus akun.
    $this->actingAs($user)
        ->delete('/settings/profile')
        ->assertStatus(405);
});
