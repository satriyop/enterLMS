<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScormPackage>
 */
class ScormPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titles = [
            'Modul Kepatuhan Anti Pencucian Uang',
            'Pelatihan Keamanan Siber Dasar',
            'Manajemen Risiko Perbankan',
            'Tata Kelola Perusahaan yang Baik',
            'Regulasi OJK: Pelaporan & Transparansi',
            'Transformasi Digital Perbankan',
            'Prudensialitas Keuangan',
        ];

        $version = fake()->randomElement(['1.2', '2004']);

        return [
            'title' => fake()->randomElement($titles),
            'version' => $version,
            'identifier' => 'ORG-'.fake()->unique()->numerify('SCORM-####'),
            'original_filename' => fake()->slug(3).'.zip',
            'disk' => 'local',
            'extraction_path' => 'scorm_packages/'.fake()->unique()->numberBetween(1, 9999),
            'entry_point' => 'index.html',
            'metadata' => [
                'schema_version' => $version === '1.2' ? '1.2' : '2004 4th Edition',
                'description' => fake('id_ID')->sentence(),
            ],
            'file_size_bytes' => fake()->numberBetween(500_000, 50_000_000),
            'uploaded_by' => User::factory(),
        ];
    }

    /**
     * SCORM 1.2 package.
     */
    public function scorm12(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => '1.2',
            'metadata' => ['schema_version' => '1.2'],
        ]);
    }

    /**
     * SCORM 2004 package.
     */
    public function scorm2004(): static
    {
        return $this->state(fn (array $attributes) => [
            'version' => '2004',
            'metadata' => ['schema_version' => '2004 4th Edition'],
        ]);
    }
}
