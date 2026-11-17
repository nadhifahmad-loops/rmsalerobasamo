<?php

use App\Models\User;

it('meredirect tamu ke halaman login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('mengizinkan pengguna terautentikasi mengunjungi beranda', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk();
});

it('meredirect halaman root sesuai status autentikasi', function () {
    $this->get('/')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/dashboard');
});
