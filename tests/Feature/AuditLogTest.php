<?php

use App\Models\AuditLog;
use App\Models\User;

it('mencatat audit saat profil diperbarui', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', ['name' => 'Nama Baru', 'email' => $user->email])
        ->assertRedirect('/settings/profile');

    $log = AuditLog::where('action', 'user.profile_updated')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id)
        ->and($log->entity_type)->toBe('user')
        ->and($log->entity_id)->toBe($user->id)
        ->and($log->metadata['changed_fields'])->toContain('name')
        ->and($log->ip_address)->not->toBeNull();
});

it('mencatat audit saat kata sandi diperbarui', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect('/settings/password');

    $log = AuditLog::where('action', 'user.password_changed')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($user->id);

    // Audit tidak boleh menyimpan nilai password.
    expect(json_encode($log->metadata))->not->toContain('new-secret-password');
});

it('tidak mencatat audit bila profil tidak berubah', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/settings/profile', ['name' => $user->name, 'email' => $user->email]);

    expect(AuditLog::where('action', 'user.profile_updated')->count())->toBe(0);
});
