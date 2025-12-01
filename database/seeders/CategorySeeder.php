<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Category Seeder
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Elektronik',
                'slug' => 'elektronik',
                'children' => [
                    ['name' => 'Telefonlar', 'slug' => 'telefonlar'],
                    ['name' => 'Bilgisayarlar', 'slug' => 'bilgisayarlar'],
                    ['name' => 'Tabletler', 'slug' => 'tabletler'],
                    ['name' => 'Aksesuarlar', 'slug' => 'aksesuarlar'],
                ],
            ],
            [
                'name' => 'Giyim',
                'slug' => 'giyim',
                'children' => [
                    ['name' => 'Erkek Giyim', 'slug' => 'erkek-giyim'],
                    ['name' => 'Kadın Giyim', 'slug' => 'kadin-giyim'],
                    ['name' => 'Çocuk Giyim', 'slug' => 'cocuk-giyim'],
                ],
            ],
            [
                'name' => 'Ev & Yaşam',
                'slug' => 'ev-yasam',
                'children' => [
                    ['name' => 'Mobilya', 'slug' => 'mobilya'],
                    ['name' => 'Dekorasyon', 'slug' => 'dekorasyon'],
                    ['name' => 'Mutfak', 'slug' => 'mutfak'],
                ],
            ],
            [
                'name' => 'Spor & Outdoor',
                'slug' => 'spor-outdoor',
                'children' => [
                    ['name' => 'Spor Ekipmanları', 'slug' => 'spor-ekipmanlari'],
                    ['name' => 'Outdoor', 'slug' => 'outdoor'],
                    ['name' => 'Fitness', 'slug' => 'fitness'],
                ],
            ],
        ];

        foreach ($categories as $index => $categoryData) {
            $parent = Category::create([
                'name' => $categoryData['name'],
                'slug' => $categoryData['slug'],
                'description' => $categoryData['name'] . ' kategorisi',
                'sort_order' => $index,
                'is_active' => true,
            ]);

            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $childIndex => $childData) {
                    Category::create([
                        'name' => $childData['name'],
                        'slug' => $childData['slug'],
                        'description' => $childData['name'] . ' alt kategorisi',
                        'parent_id' => $parent->id,
                        'sort_order' => $childIndex,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
