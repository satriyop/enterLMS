<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Banking / compliance categories for Enteraksi LMS.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Kepatuhan Perbankan',
                'description' => 'Regulasi OJK, GCG, dan kontrol internal lembaga jasa keuangan.',
                'order' => 1,
            ],
            [
                'name' => 'APU-PPT',
                'description' => 'Anti pencucian uang, pencegahan pendanaan terorisme, dan due diligence nasabah.',
                'order' => 2,
            ],
            [
                'name' => 'Transformasi Digital',
                'description' => 'Digital banking, open banking, API, dan keamanan siber perbankan.',
                'order' => 3,
            ],
            [
                'name' => 'Manajemen Risiko',
                'description' => 'Basel, risiko kredit, operasional, likuiditas, dan framework risiko bank.',
                'order' => 4,
            ],
            [
                'name' => 'Dasar Perbankan',
                'description' => 'Onboarding industri perbankan Indonesia, produk, dan layanan bank.',
                'order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'order' => $category['order'],
                ]
            );
        }
    }
}
