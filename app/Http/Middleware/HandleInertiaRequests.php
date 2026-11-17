<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'role' => $user->role?->name,
                    'permissions' => $this->permissionsFor($user),
                ] : null,
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Resolve the role permissions this user currently has.
     *
     * @return array<string, bool>
     */
    private function permissionsFor($user): array
    {
        return collect([
            'manage-users',
            'manage-catalog',
            'manage-tables',
            'manage-shifts',
            'view-reports',
            'manage-settings',
            'void-paid',
        ])->mapWithKeys(fn (string $ability) => [$ability => Gate::forUser($user)->allows($ability)])
            ->all();
    }
}
