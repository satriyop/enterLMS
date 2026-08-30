<?php

namespace App\Console\Commands;

use App\Domain\Shared\Academy;
use App\Domain\Shared\AcademyPresetCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

#[Signature('lms:preset {preset? : academy, academic, or corporate} {--show : Tampilkan capability tanpa menulis .env} {--dry-run : Tampilkan perubahan .env tanpa menulis} {--env-file= : Path file env (default: .env)}')]
#[Description('Pasang atau periksa preset capability instalasi LMS (ADR 010)')]
class LmsPresetCommand extends Command
{
    public function handle(): int
    {
        $presetArgument = $this->argument('preset');
        $preset = is_string($presetArgument) ? $presetArgument : null;

        if ($preset === null || $this->option('show')) {
            return $this->showResolved($preset);
        }

        try {
            $resolved = AcademyPresetCatalog::resolve($preset);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $envFileOption = $this->option('env-file');
        $envFile = is_string($envFileOption) && $envFileOption !== ''
            ? $envFileOption
            : $this->laravel->environmentFilePath();
        $line = 'LMS_PRESET='.$preset;

        if ($this->option('dry-run')) {
            $this->info("DRY RUN — akan menulis {$line} ke {$envFile}");
            $this->printResolved($resolved);

            return self::SUCCESS;
        }

        $this->writeEnvKey($envFile, 'LMS_PRESET', $preset);

        if ($this->laravel->configurationIsCached()) {
            Artisan::call('config:clear');
            $this->warn('Config cache dibersihkan. Jalankan config:cache lagi di production.');
        }

        Academy::using($preset);
        $this->info("Preset dipasang: {$preset}");
        $this->printResolved($resolved);

        return self::SUCCESS;
    }

    private function showResolved(?string $preset): int
    {
        try {
            $resolved = $preset === null
                ? [
                    'preset' => Academy::preset(),
                    'features' => config('lms.features'),
                    'labels' => config('lms.labels'),
                    'identity' => config('lms.identity'),
                ]
                : AcademyPresetCatalog::resolve($preset);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->printResolved($resolved);

        return self::SUCCESS;
    }

    /**
     * @param  array{
     *     preset: string,
     *     features: array<string, bool>,
     *     labels: array<string, string>,
     *     identity: array{scheme: string, label: string}
     * }  $resolved
     */
    private function printResolved(array $resolved): void
    {
        $this->line('Preset: '.$resolved['preset']);
        $this->line('Identity: '.$resolved['identity']['label'].' ('.$resolved['identity']['scheme'].')');
        $this->newLine();
        $this->table(
            ['Feature', 'On'],
            collect($resolved['features'])
                ->map(fn (bool $on, string $name): array => [$name, $on ? 'yes' : 'no'])
                ->values()
                ->all(),
        );
        $this->table(
            ['Label', 'Value'],
            collect($resolved['labels'])
                ->map(fn (string $value, string $name): array => [$name, $value])
                ->values()
                ->all(),
        );
    }

    private function writeEnvKey(string $path, string $key, string $value): void
    {
        $contents = is_file($path) ? (string) file_get_contents($path) : '';
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $contents) === 1) {
            $contents = (string) preg_replace($pattern, $line, $contents, 1);
        } else {
            $contents = rtrim($contents)."\n".$line."\n";
        }

        file_put_contents($path, $contents);
    }
}
