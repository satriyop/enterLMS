<?php

namespace App\Console\Commands;

use App\Models\AgentWebhookEndpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManageAgentWebhook extends Command
{
    protected $signature = 'agent:webhook
                            {action : list|register|disable|enable}
                            {--name= : Endpoint display name}
                            {--url= : HTTPS callback URL}
                            {--secret= : HMAC secret (auto-generated if omitted on register)}
                            {--events= : Comma-separated events (default: all supported)}
                            {--id= : Endpoint id for enable/disable}';

    protected $description = 'Manage outbound agent webhook endpoints (Hermes/OpenClaw push)';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list' => $this->listEndpoints(),
            'register' => $this->registerEndpoint(),
            'disable' => $this->setActive(false),
            'enable' => $this->setActive(true),
            default => $this->invalidAction(),
        };
    }

    private function listEndpoints(): int
    {
        $rows = AgentWebhookEndpoint::query()->orderBy('id')->get()->map(fn (AgentWebhookEndpoint $e) => [
            $e->id,
            $e->name,
            $e->is_active ? 'yes' : 'no',
            implode(',', $e->events ?? []),
            Str::limit($e->url, 48),
        ])->all();

        $this->table(['id', 'name', 'active', 'events', 'url'], $rows);

        return self::SUCCESS;
    }

    private function registerEndpoint(): int
    {
        $url = (string) $this->option('url');
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('URL valid wajib: --url=https://...');

            return self::FAILURE;
        }

        try {
            $events = $this->resolveEvents();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $secret = $this->option('secret') ?: Str::random(48);
        $name = $this->option('name') ?: 'webhook-'.now()->format('YmdHis');

        $endpoint = AgentWebhookEndpoint::query()->create([
            'name' => $name,
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'is_active' => true,
            'max_attempts' => 3,
        ]);

        $this->info("Webhook #{$endpoint->id} terdaftar ({$endpoint->name}).");
        $this->line('Events: '.implode(', ', $events));
        $this->warn('Secret (simpan sekarang): '.$secret);

        return self::SUCCESS;
    }

    private function setActive(bool $active): int
    {
        $id = (int) $this->option('id');
        if ($id < 1) {
            $this->error('--id= wajib untuk enable/disable.');

            return self::FAILURE;
        }

        $endpoint = AgentWebhookEndpoint::query()->find($id);
        if ($endpoint === null) {
            $this->error("Endpoint #{$id} tidak ditemukan.");

            return self::FAILURE;
        }

        $endpoint->update(['is_active' => $active]);
        $this->info("Endpoint #{$id} ".($active ? 'diaktifkan' : 'dinonaktifkan').'.');

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('action harus: list|register|disable|enable');

        return self::FAILURE;
    }

    /**
     * @return list<string>
     */
    private function resolveEvents(): array
    {
        $raw = $this->option('events');
        if (! $raw) {
            return AgentWebhookEndpoint::SUPPORTED_EVENTS;
        }

        $events = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
        foreach ($events as $event) {
            if (! in_array($event, AgentWebhookEndpoint::SUPPORTED_EVENTS, true)) {
                throw new \InvalidArgumentException(
                    "Event tidak didukung: {$event}. Supported: ".implode(', ', AgentWebhookEndpoint::SUPPORTED_EVENTS)
                );
            }
        }

        return $events;
    }
}
