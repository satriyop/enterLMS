<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Pengenalan Agen',
                'description' => 'Kursus terbuka tentang agen AI dan academy EnterLMS.',
                'order' => 1,
            ],
            [
                'name' => 'Operasi Agen',
                'description' => 'Kursus terbatas untuk Operator yang menjalankan runtime agen.',
                'order' => 2,
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
