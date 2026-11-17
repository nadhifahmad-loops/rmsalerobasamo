<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Membuat dummy foto produk lokal dari nama produk di CatalogSeeder.
 *
 * Aset sumber ditulis ke public/images/products/demo agar bisa ikut repository,
 * lalu disalin ke disk public agar image_url accessor langsung bisa dipakai.
 * Seeder ini hanya mengisi produk yang belum punya foto atau masih memakai
 * dummy image, sehingga upload admin/owner tidak akan ditimpa.
 */
class DemoProductImageSeeder extends Seeder
{
    /**
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const PRODUCTS = [
        'Nasi Putih' => ['Nasi & Makanan Utama', 'rice', '#E9E4DD'],
        'Nasi Rendang' => ['Nasi & Makanan Utama', 'curry', '#D50112'],
        'Nasi Ayam Pop' => ['Nasi & Makanan Utama', 'chicken', '#FDCE02'],
        'Nasi Goreng Padang' => ['Nasi & Makanan Utama', 'fried-rice', '#FDCE02'],
        'Mie Goreng Padang' => ['Nasi & Makanan Utama', 'noodle', '#FDCE02'],
        'Nasi Gulai Kambing' => ['Nasi & Makanan Utama', 'curry', '#D50112'],
        'Rendang Daging' => ['Lauk Padang', 'curry', '#D50112'],
        'Ayam Pop' => ['Lauk Padang', 'chicken', '#FDCE02'],
        'Gulai Ayam' => ['Lauk Padang', 'curry', '#D50112'],
        'Gulai Kambing' => ['Lauk Padang', 'curry', '#D50112'],
        'Balado Telur' => ['Lauk Padang', 'egg', '#FDCE02'],
        'Dendeng Balado' => ['Lauk Padang', 'chili', '#D50112'],
        'Asam Padeh Ikan' => ['Lauk Padang', 'fish', '#D50112'],
        'Sayur Nangka' => ['Lauk Padang', 'curry', '#FDCE02'],
        'Sambal Hijau' => ['Lauk Padang', 'chili', '#FDCE02'],
        'Sate Ayam' => ['Sate & Bakar', 'sate', '#D50112'],
        'Sate Kambing' => ['Sate & Bakar', 'sate', '#D50112'],
        'Ayam Bakar' => ['Sate & Bakar', 'chicken', '#FDCE02'],
        'Ikan Bakar' => ['Sate & Bakar', 'fish', '#D50112'],
        'Teh Tawar' => ['Minuman', 'drink', '#FDCE02'],
        'Teh Manis' => ['Minuman', 'drink', '#FDCE02'],
        'Es Teh Manis' => ['Minuman', 'drink', '#FDCE02'],
        'Kopi Hitam' => ['Minuman', 'drink', '#211D1B'],
        'Air Mineral' => ['Minuman', 'drink', '#E9E4DD'],
        'Keripik Singkong' => ['Camilan & Dessert', 'snack', '#FDCE02'],
        'Pisang Goreng' => ['Camilan & Dessert', 'snack', '#FDCE02'],
    ];

    /**
     * @return array<int, string>
     */
    public static function productNames(): array
    {
        return array_keys(self::PRODUCTS);
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoProductImageSeeder tidak boleh dijalankan di environment production.');
        }

        $sourceDirectory = public_path('images/products/demo');
        File::ensureDirectoryExists($sourceDirectory, 0755, true);

        foreach (self::PRODUCTS as $name => [$category, $motif, $accent]) {
            $slug = Str::slug($name);
            $fileName = $slug.'.svg';
            $relativePath = 'products/demo/'.$fileName;
            $svg = $this->svg($name, $category, $motif, $accent);

            File::put($sourceDirectory.DIRECTORY_SEPARATOR.$fileName, $svg);
            Storage::disk('public')->put($relativePath, $svg, 'public');

            Product::query()
                ->where('name', $name)
                ->whereHas('category', fn ($query) => $query->where('name', $category))
                ->where(function ($query) use ($relativePath): void {
                    $query->whereNull('image_path')
                        ->orWhere('image_path', 'like', 'products/demo/%')
                        ->orWhere('image_path', $relativePath);
                })
                ->update(['image_path' => $relativePath]);
        }
    }

    private function svg(string $name, string $category, string $motif, string $accent): string
    {
        $safeName = e($name);
        $safeCategory = e($category);
        $art = $this->motif($motif, $accent);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" role="img" aria-labelledby="title desc">
          <title id="title">{$safeName}</title>
          <desc id="desc">Dummy foto produk {$safeName} untuk katalog lokal Salero Basamo Jati.</desc>
          <rect width="640" height="480" fill="#FAF9F6" />
          <rect width="640" height="12" fill="#D50112" />
          <rect x="24" y="28" width="244" height="36" rx="18" fill="#FFF0F1" />
          <text x="42" y="52" fill="#D50112" font-family="system-ui, sans-serif" font-size="16" font-weight="700">{$safeCategory}</text>
          <rect x="540" y="28" width="76" height="36" rx="18" fill="#FDCE02" />
          <text x="578" y="52" fill="#211D1B" font-family="system-ui, sans-serif" font-size="14" font-weight="700" text-anchor="middle">DEMO</text>
          <circle cx="320" cy="232" r="134" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="8" />
          <circle cx="320" cy="232" r="108" fill="none" stroke="#FAF9F6" stroke-width="4" />
          {$art}
          <rect x="150" y="388" width="340" height="2" fill="#E9E4DD" />
          <text x="320" y="432" fill="#211D1B" font-family="system-ui, sans-serif" font-size="34" font-weight="700" text-anchor="middle">{$safeName}</text>
        </svg>
        SVG;
    }

    private function motif(string $motif, string $accent): string
    {
        return match ($motif) {
            'rice' => $this->rice(),
            'fried-rice' => $this->friedRice(),
            'noodle' => $this->noodle(),
            'curry' => $this->curry($accent),
            'chicken' => $this->chicken($accent),
            'egg' => $this->egg($accent),
            'chili' => $this->chili($accent),
            'fish' => $this->fish($accent),
            'sate' => $this->sate($accent),
            'drink' => $this->drink($accent),
            'snack' => $this->snack($accent),
            default => $this->curry($accent),
        };
    }

    private function rice(): string
    {
        return <<<'SVG'
        <ellipse cx="320" cy="232" rx="82" ry="54" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="6" />
        <path d="M272 218c22-12 44-12 64 0M264 238c26-12 54-12 80 0M284 258c18-10 36-10 54 0" fill="none" stroke="#E9E4DD" stroke-width="6" stroke-linecap="round" />
        SVG;
    }

    private function friedRice(): string
    {
        return <<<'SVG'
        <ellipse cx="320" cy="232" rx="84" ry="56" fill="#FFFDF6" stroke="#E9E4DD" stroke-width="6" />
        <circle cx="288" cy="214" r="6" fill="#FDCE02" />
        <circle cx="338" cy="208" r="5" fill="#D50112" />
        <circle cx="356" cy="244" r="6" fill="#FDCE02" />
        <circle cx="306" cy="260" r="5" fill="#D50112" />
        <path d="M274 236c28-12 58-12 86 0" fill="none" stroke="#E9E4DD" stroke-width="5" stroke-linecap="round" />
        SVG;
    }

    private function noodle(): string
    {
        return <<<'SVG'
        <path d="M248 200c34-30 68 30 102 0s52 24 44 44M248 230c34-30 68 30 102 0s52 24 44 44M260 264c30-24 62 20 92-2" fill="none" stroke="#FDCE02" stroke-width="14" stroke-linecap="round" />
        <circle cx="286" cy="214" r="8" fill="#D50112" />
        <circle cx="352" cy="254" r="7" fill="#D50112" />
        SVG;
    }

    private function curry(string $accent): string
    {
        return <<<SVG
        <circle cx="320" cy="232" r="78" fill="{$accent}" opacity="0.18" />
        <circle cx="320" cy="232" r="64" fill="{$accent}" opacity="0.88" />
        <circle cx="296" cy="212" r="10" fill="#FDCE02" />
        <circle cx="344" cy="224" r="12" fill="#FDCE02" />
        <circle cx="314" cy="262" r="9" fill="#FDCE02" />
        <circle cx="356" cy="268" r="6" fill="#FDCE02" />
        SVG;
    }

    private function chicken(string $accent): string
    {
        return <<<SVG
        <path d="M268 276c-18-34 4-76 44-88 28-8 54 4 66 26 14 26 4 56-22 70-28 16-70 12-88-8Z" fill="{$accent}" opacity="0.92" />
        <path d="M356 190c22-20 44-22 62-8-16 18-36 26-58 22Z" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="5" />
        <circle cx="304" cy="222" r="8" fill="#FFFFFF" opacity="0.8" />
        <circle cx="334" cy="248" r="6" fill="#FFFFFF" opacity="0.8" />
        SVG;
    }

    private function egg(string $accent): string
    {
        return <<<SVG
        <ellipse cx="290" cy="236" rx="34" ry="42" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="5" />
        <ellipse cx="350" cy="236" rx="34" ry="42" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="5" />
        <path d="M278 214c10 12 14 30 8 44M338 214c10 12 14 30 8 44" fill="none" stroke="{$accent}" stroke-width="8" stroke-linecap="round" />
        SVG;
    }

    private function chili(string $accent): string
    {
        return <<<SVG
        <path d="M260 204c34-4 66 12 84 40-30 12-66 6-90-18Z" fill="{$accent}" />
        <path d="M380 268c-30 12-64 2-84-24 30-14 66-8 90 12Z" fill="{$accent}" opacity="0.72" />
        <path d="M258 200c-12-12-10-26 2-34M382 268c12 10 14 24 4 34" fill="none" stroke="#211D1B" stroke-width="7" stroke-linecap="round" />
        SVG;
    }

    private function fish(string $accent): string
    {
        return <<<SVG
        <path d="M250 232c34-42 88-42 122 0-34 42-88 42-122 0Z" fill="#E9E4DD" stroke="#FFFFFF" stroke-width="6" />
        <path d="M372 232l44-30v60Z" fill="{$accent}" />
        <circle cx="286" cy="226" r="7" fill="#211D1B" />
        <path d="M300 252c28 12 52 10 74-4" fill="none" stroke="{$accent}" stroke-width="7" stroke-linecap="round" />
        SVG;
    }

    private function sate(string $accent): string
    {
        return <<<SVG
        <path d="M248 288l144-112M268 300l144-112" stroke="#211D1B" stroke-width="8" stroke-linecap="round" />
        <rect x="282" y="218" width="34" height="28" rx="8" fill="{$accent}" transform="rotate(-38 282 218)" />
        <rect x="316" y="196" width="34" height="28" rx="8" fill="{$accent}" transform="rotate(-38 316 196)" />
        <rect x="306" y="240" width="34" height="28" rx="8" fill="{$accent}" transform="rotate(-38 306 240)" opacity="0.72" />
        <rect x="340" y="218" width="34" height="28" rx="8" fill="{$accent}" transform="rotate(-38 340 218)" opacity="0.72" />
        SVG;
    }

    private function drink(string $accent): string
    {
        return <<<SVG
        <path d="M276 178h88l-12 112a18 18 0 0 1-18 16h-28a18 18 0 0 1-18-16Z" fill="#FFFFFF" stroke="#E9E4DD" stroke-width="6" />
        <path d="M282 224h76l-6 66a14 14 0 0 1-14 12h-36a14 14 0 0 1-14-12Z" fill="{$accent}" opacity="0.82" />
        <path d="M372 190l36-40" stroke="#211D1B" stroke-width="8" stroke-linecap="round" />
        <circle cx="304" cy="198" r="5" fill="#FFFFFF" />
        <circle cx="330" cy="188" r="4" fill="#FFFFFF" />
        SVG;
    }

    private function snack(string $accent): string
    {
        return <<<SVG
        <ellipse cx="292" cy="212" rx="46" ry="28" fill="{$accent}" transform="rotate(-18 292 212)" />
        <ellipse cx="352" cy="228" rx="46" ry="28" fill="{$accent}" opacity="0.72" transform="rotate(16 352 228)" />
        <ellipse cx="314" cy="270" rx="44" ry="24" fill="{$accent}" opacity="0.9" transform="rotate(-8 314 270)" />
        <circle cx="282" cy="204" r="5" fill="#D50112" />
        <circle cx="356" cy="222" r="5" fill="#D50112" />
        <circle cx="318" cy="264" r="5" fill="#D50112" />
        SVG;
    }
}
