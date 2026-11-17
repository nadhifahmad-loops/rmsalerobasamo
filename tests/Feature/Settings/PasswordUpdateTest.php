<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('mengizinkan kata sandi diperbarui', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect('/settings/password')
        ->assertSessionHasNoErrors();

    expect(Hash::check('new-secret-password', $user->refresh()->password))->toBeTrue();
});

it('menolak kata sandi saat ini yang salah', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect('/settings/password')
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('menolak konfirmasi kata sandi yang tidak cocok', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('menolak kata sandi yang terlalu lemah', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('tidak mengizinkan tamu memperbarui kata sandi', function () {
    $this->put('/settings/password', [
        'current_password' => 'password',
        'password' => 'new-secret-password',
        'password_confirmation' => 'new-secret-password',
    ])->assertRedirect('/login');
});
