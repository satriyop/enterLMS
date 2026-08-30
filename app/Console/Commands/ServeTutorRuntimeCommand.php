<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ServeTutorRuntimeCommand extends Command
{
    protected $signature = 'tutor:serve
                            {--host=127.0.0.1 : Bind address}
                            {--port=9273 : Bind port}';

    protected $description = 'Serve the Tutor runtime sidecar so PHP-FPM can call Hermes as this user';

    public function handle(): int
    {
        $host = (string) $this->option('host');
        $port = (int) $this->option('port');
        $router = base_path('bin/tutor-runtime-server.php');

        if (! is_file($router)) {
            $this->error('Tutor runtime router is missing.');

            return self::FAILURE;
        }

        $apiKey = (string) (config('tutor.runtime_api_key') ?: config('tutor.runtime_secret'));
        if ($apiKey === '') {
            $this->warn('TUTOR_RUNTIME_API_KEY is empty. Set it in .env before using this adapter.');
        }

        $profile = trim((string) config('tutor.hermes_profile', ''));
        if ($profile === '') {
            $this->warn('TUTOR_HERMES_PROFILE is empty. Sidecar will use the default Hermes profile.');
        } else {
            $this->line("Hermes profile: {$profile}");
        }

        $this->info("Tutor runtime listening on http://{$host}:{$port}");
        $this->line('Run as your user (not _www). Leave this process running.');

        $php = PHP_BINARY;
        $exit = self::FAILURE;
        passthru(escapeshellarg($php).' -S '.escapeshellarg($host.':'.$port).' '.escapeshellarg($router), $exit);

        return $exit;
    }
}
