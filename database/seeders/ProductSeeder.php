<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Product Seeder
 */
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'iPhone 15 Pro Max',
                'slug' => 'iphone-15-pro-max',
                'sku' => 'IPH-15-PMAX-256',
                'description' => 'Apple iPhone 15 Pro Max 256GB - Titanium Blue. A17 Pro çip, 48MP kamera sistemi.',
                'price' => 7499900, // 74.999 TL
                'compare_price' => 7999900,
                'quantity' => 50,
                'categories' => ['telefonlar'],
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'sku' => 'SAM-S24-ULTRA-256',
                'description' => 'Samsung Galaxy S24 Ultra 256GB - Titanium Gray. Galaxy AI özellikleri ile.',
                'price' => 6499900,
                'compare_price' => 6999900,
                'quantity' => 35,
                'categories' => ['telefonlar'],
            ],
            [
                'name' => 'MacBook Pro 14" M3 Pro',
                'slug' => 'macbook-pro-14-m3-pro',
                'sku' => 'MBP-14-M3P-512',
                'description' => 'Apple MacBook Pro 14" M3 Pro çip, 18GB RAM, 512GB SSD.',
                'price' => 8999900,
                'quantity' => 20,
                'categories' => ['bilgisayarlar'],
            ],
            [
                'name' => 'iPad Pro 12.9" M2',
                'slug' => 'ipad-pro-12-9-m2',
                'sku' => 'IPAD-PRO-129-256',
                'description' => 'Apple iPad Pro 12.9" M2 çip, 256GB, WiFi + Cellular.',
                'price' => 4499900,
                'compare_price' => 4799900,
                'quantity' => 25,
                'categories' => ['tabletler'],
            ],
            [
                'name' => 'AirPods Pro 2. Nesil',
                'slug' => 'airpods-pro-2',
                'sku' => 'APP-2-USB-C',
                'description' => 'Apple AirPods Pro 2. Nesil USB-C şarj kutulu.',
                'price' => 899900,
                'quantity' => 100,
                'categories' => ['aksesuarlar'],
            ],
            [
                'name' => 'Nike Air Max 270',
                'slug' => 'nike-air-max-270',
                'sku' => 'NIKE-AM270-BLK-42',
                'description' => 'Nike Air Max 270 - Siyah, 42 Numara.',
                'price' => 349900,
                'compare_price' => 399900,
                'quantity' => 45,
                'categories' => ['erkek-giyim', 'spor-ekipmanlari'],
            ],
            [
                'name' => 'Ergonomik Ofis Koltuğu',
                'slug' => 'ergonomik-ofis-koltugu',
                'sku' => 'ERG-CHAIR-PRO',
                'description' => 'Profesyonel ergonomik ofis koltuğu, ayarlanabilir bel desteği.',
                'price' => 1299900,
                'quantity' => 15,
                'categories' => ['mobilya'],
            ],
            [
                'name' => 'Treadmill Pro X500',
                'slug' => 'treadmill-pro-x500',
                'sku' => 'TRD-PRO-X500',
                'description' => 'Profesyonel koşu bandı, 20km/h max hız, 15% eğim.',
                'price' => 2499900,
                'compare_price' => 2999900,
                'quantity' => 8,
                'categories' => ['fitness', 'spor-ekipmanlari'],
            ],
        ];

        foreach ($products as $productData) {
            $categoryIds = [];
            if (isset($productData['categories'])) {
                $categorySlugs = $productData['categories'];
                unset($productData['categories']);
                $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id')->toArray();
            }

            $product = Product::create([
                'uuid' => (string) Str::uuid(),
                'name' => $productData['name'],
                'slug' => $productData['slug'],
                'sku' => $productData['sku'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'compare_price' => $productData['compare_price'] ?? null,
                'quantity' => $productData['quantity'],
                'is_active' => true,
                'is_featured' => rand(0, 1) === 1,
                'published_at' => now(),
            ]);

            if (!empty($categoryIds)) {
                $product->categories()->attach($categoryIds);
            }
        }

        // Generate additional random products
        Product::factory()
            ->count(20)
            ->active()
            ->create()
            ->each(function (Product $product) {
                $randomCategories = Category::whereNotNull('parent_id')
                    ->inRandomOrder()
                    ->take(rand(1, 3))
                    ->pluck('id');
                $product->categories()->attach($randomCategories);
            });
    }
}
