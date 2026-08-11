<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Banking / compliance tags for Enteraksi LMS.
 */
class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'OJK',
            'POJK',
            'Kepatuhan',
            'GCG',
            'APU-PPT',
            'AML',
            'CDD',
            'EDD',
            'Basel III',
            'Manajemen Risiko',
            'Risiko Kredit',
            'Risiko Operasional',
            'Digital Banking',
            'Open Banking',
            'Keamanan Siber',
            'Kontrol Internal',
            'Onboarding',
            'Produk Bank',
            'Sertifikasi Internal',
            'Pelatihan Wajib',
        ];

        foreach ($tags as $tagName) {
            Tag::query()->firstOrCreate(
                ['slug' => Str::slug($tagName)],
                ['name' => $tagName]
            );
        }
    }
}
