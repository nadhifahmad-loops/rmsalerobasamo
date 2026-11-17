<?php

namespace Database\Seeders;

use App\Models\DiningTable;
use Illuminate\Database\Seeder;

/**
 * Meja contoh untuk rumah makan. Hanya dipanggil oleh DemoDataSeeder.
 */
class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        for ($number = 1; $number <= 12; $number++) {
            $tableNumber = str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            DiningTable::firstOrCreate(
                ['table_number' => $tableNumber],
                [
                    'label' => 'Meja '.$tableNumber,
                    'is_active' => true,
                ],
            );
        }
    }
}
