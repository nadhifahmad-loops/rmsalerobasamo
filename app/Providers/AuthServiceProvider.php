<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot any application services.
     *
     * Role matrix from docs/USER-FLOWS.md §6. These gates are the
     * server-side authorization source of truth; every protected route
     * must call Gate/Policy — hiding a menu in the UI is not enough.
     */
    public function boot(): void
    {
        $permissions = [
            'manage-users' => ['admin', 'owner'],
            'manage-catalog' => ['admin', 'owner'],
            'manage-tables' => ['admin', 'owner'],
            'manage-shifts' => ['admin', 'owner'],
            'view-reports' => ['admin', 'owner'],
            'manage-settings' => ['admin', 'owner'],
            'void-paid' => ['admin', 'owner'],
        ];

        foreach ($permissions as $ability => $roles) {
            Gate::define($ability, function (User $user) use ($roles) {
                return $user->hasRole(...$roles);
            });
        }
    }
}
