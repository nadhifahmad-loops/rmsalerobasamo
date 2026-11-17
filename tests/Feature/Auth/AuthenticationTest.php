<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

it('menampilkan halaman login untuk tamu', function () {
    $this->get('/login')->assertOk();
});

it('redirect tamu dari halaman terproteksi ke login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/settings/profile')->assertRedirect('/login');
    $this->get('/settings/password')->assertRedirect('/login');
    $this->get('/settings/appearance')->assertRedirect('/login');
});

it('mengautentikasi pengguna menggunakan username', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'login' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('mengautentikasi pengguna menggunakan email', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

it('menolak password yang salah dengan pesan generik', function () {
    $user = User::factory()->create();

    $response = $this->from('/login')->post('/login', [
        'login' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->not->toContain($user->username);
});

it('menolak login tanpa kredensial', function () {
    $response = $this->from('/login')->post('/login', []);

    $this->assertGuest();
    $response->assertSessionHasErrors(['login', 'password']);
});

it('menolak akun nonaktif setelah password benar', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->from('/login')->post('/login', [
        'login' => $user->username,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toBe(__('auth.inactive'));
});

it('menolak username yang tidak terdaftar', function () {
    User::factory()->create();

    $response = $this->from('/login')->post('/login', [
        'login' => 'pengguna-tidak-ada',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toBe(__('auth.failed'));
});

it('membatasi percobaan login berulang', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $this->post('/login', [
            'login' => $user->username,
            'password' => 'wrong-password',
        ]);
    }

    // Percobaan ke-7 dengan password yang benar tetap diblokir oleh rate limiter.
    $response = $this->from('/login')->post('/login', [
        'login' => $user->username,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('login');
});

it('mencabut sesi pengguna yang dinonaktifkan setelah login', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->update(['is_active' => false]);

    $response = $this->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toBe(__('auth.inactive'));
});

it('mengeluarkan pengguna dari sesi (logout)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

it('menolak logout untuk tamu', function () {
    $this->post('/logout')->assertRedirect('/login');
});

it('tidak menyediakan route registrasi publik', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

it('tidak menyediakan route reset password', function () {
    $this->get('/forgot-password')->assertNotFound();
    $this->post('/forgot-password', [])->assertNotFound();
});

it('mereset rate limiter setelah login berhasil', function () {
    $user = User::factory()->create();

    RateLimiter::hit(Str::lower($user->username).'|127.0.0.1');

    $this->post('/login', [
        'login' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    expect(RateLimiter::remaining(Str::lower($user->username).'|127.0.0.1', 5))->toBe(5);
});
