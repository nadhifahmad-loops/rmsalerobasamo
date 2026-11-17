<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Asumsi Phase 1: password minimal 8 karakter dan mengandung huruf.
        // Kebijakan ini dipakai untuk ganti password (settings) dan user:create.
        Password::defaults(fn () => Password::min(8)->letters());
    }
}
