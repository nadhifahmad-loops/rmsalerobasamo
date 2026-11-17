<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $rows = <<<'DATA'
Nasi & Makanan Utama|Nasi Putih|porsi|Nasi putih hangat, pendamping lauk Padang.|Reguler:5000,Large:8000
Nasi & Makanan Utama|Nasi Rendang|porsi|Nasi dengan rendang daging dan sambal hijau.|Reguler:28000,Pedas:29000,Extra Daging:38000
Nasi & Makanan Utama|Nasi Ayam Pop|porsi|Ayam pop khas Padang dengan nasi dan sambal.|Reguler:24000,Extra:30000
Nasi & Makanan Utama|Nasi Goreng Padang|porsi|Nasi goreng dengan bumbu Padang dan telur.|Reguler:26000,Pedas:27000
Nasi & Makanan Utama|Mie Goreng Padang|porsi|Mie goreng dengan irisan ayam dan sayur.|Reguler:24000,Pedas:25000
Nasi & Makanan Utama|Nasi Gulai Kambing|porsi|Nasi dengan gulai kambing dan acar.|Reguler:36000,Extra:44000
Lauk Padang|Rendang Daging|porsi|Daging sapi dimasak lama dengan bumbu rendang.|Reguler:35000,Extra:45000
Lauk Padang|Ayam Pop|porsi|Ayam khas Payakumbuh dengan sambal hijau.|Reguler:24000,Extra:29000
Lauk Padang|Gulai Ayam|porsi|Gulai ayam dengan kuah kuning gurih.|Reguler:25000,Pedas:26000
Lauk Padang|Gulai Kambing|porsi|Gulai kambing dengan rempah Padang.|Reguler:32000,Extra:40000
Lauk Padang|Balado Telur|porsi|Telur rebus dibumbui cabai balado.|Reguler:12000,Pedas:13000
Lauk Padang|Dendeng Balado|porsi|Daging tipis goreng dengan sambal balado.|Reguler:38000,Extra:48000
Lauk Padang|Asam Padeh Ikan|porsi|Ikan kuah asam pedas khas Padang.|Reguler:30000,Pedas:31000
Lauk Padang|Sayur Nangka|porsi|Nangka muda dimasak dengan bumbu gulai.|Reguler:10000
Lauk Padang|Sambal Hijau|porsi|Sambal hijau Padang dengan cabai rawit.|Reguler:3000,Extra:5000
Sate & Bakar|Sate Ayam|tusuk|Sate ayam dengan bumbu kacang Padang.|Reguler:4000,Pedas:4500
Sate & Bakar|Sate Kambing|tusuk|Sate kambing muda dengan kecap dan sambal.|Reguler:6000,Extra:7000
Sate & Bakar|Ayam Bakar|porsi|Ayam bakar arang dengan bumbu kunyit.|Reguler:27000,Madu:30000
Sate & Bakar|Ikan Bakar|porsi|Ikan bakar dengan sambal kecap Padang.|Reguler:32000,Sambal Khas:34000
Minuman|Teh Tawar|gelas|Teh tawar hangat atau dingin.|Hangat:4000,Dingin:5000
Minuman|Teh Manis|gelas|Teh manis khas rumah makan.|Reguler:5000,Dingin:6000
Minuman|Es Teh Manis|gelas|Es teh manis dengan ukuran reguler dan large.|Reguler:6000,Large:8000
Minuman|Kopi Hitam|gelas|Kopi hitam lokal, hangat atau dingin.|Hangat:6000,Dingin:7000
Minuman|Air Mineral|botol|Air mineral kemasan.|Reguler:4000,Large:6000
Camilan & Dessert|Keripik Singkong|piring|Keripik singkong renyah dengan bumbu balado.|Reguler:10000,Pedas:11000
Camilan & Dessert|Pisang Goreng|porsi|Pisang goreng madu dengan taburan gula.|Reguler:12000,Extra:15000
DATA;

        $categories = [];
        $sortOrder = 1;

        foreach (explode("\n", trim($rows)) as $line) {
            [$categoryName, $productName, $unit, $description, $variantText] = explode('|', $line, 5);

            if (! isset($categories[$categoryName])) {
                $categories[$categoryName] = Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['is_active' => true, 'sort_order' => $sortOrder++],
                );
            }

            $product = Product::firstOrCreate(
                ['category_id' => $categories[$categoryName]->id, 'name' => $productName],
                [
                    'description' => $description,
                    'unit' => $unit,
                    'is_active' => true,
                    'is_available' => true,
                ],
            );

            $isFirst = true;

            foreach (explode(',', $variantText) as $variant) {
                [$variantName, $price] = explode(':', $variant, 2);

                ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'name' => $variantName],
                    [
                        'price' => (int) $price,
                        'is_default' => $isFirst,
                        'is_active' => true,
                        'is_available' => true,
                    ],
                );

                $isFirst = false;
            }
        }
    }
}
