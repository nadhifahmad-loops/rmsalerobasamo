<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Entry point untuk data dummy lokal. Tidak dipanggil oleh DatabaseSeeder
 * agar `php artisan db:seed` di produksi tidak pernah membuat data contoh.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoDataSeeder tidak boleh dijalankan di environment production.');
        }

        $this->call([
            RoleSeeder::class,
            CatalogSeeder::class,
            DemoProductImageSeeder::class,
            DiningTableSeeder::class,
            DemoTransactionSeeder::class,
        ]);
    }
}
