<?php

namespace App\Mcp\Tools\Tutor;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\ChannelIdentity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('resolve')]
#[Description('Map a linked WhatsApp phone or numeric Telegram user id to the named Learner user_id. Telegram identifier is digits only (inbound user_id/chat_id), never the display name.')]
#[IsReadOnly]
#[IsIdempotent]
class ResolveChannelTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::TUTOR_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'channel' => ['required', 'string', Rule::in(ChannelIdentity::channels())],
                'identifier' => ['required', 'string', 'max:191'],
            ], [
                'channel.required' => 'channel wajib diisi.',
                'channel.in' => 'channel harus whatsapp atau telegram.',
                'identifier.required' => 'identifier wajib diisi.',
            ]);

            $channel = $validated['channel'];
            $identifier = ChannelIdentity::normalize($channel, $validated['identifier']);

            if ($identifier === '') {
                return Response::error(
                    $channel === ChannelIdentity::CHANNEL_TELEGRAM
                        ? 'ID Telegram harus angka ID akun (user_id/chat_id inbound), bukan nama tampilan.'
                        : 'Nomor WhatsApp tidak valid.'
                );
            }

            $link = ChannelIdentity::query()
                ->where('channel', $channel)
                ->where('identifier', $identifier)
                ->first();

            if ($link === null) {
                return Response::error('Identitas kanal belum tertaut ke Learner.');
            }

            return Response::structured([
                'ok' => true,
                'data' => [
                    'user_id' => $link->user_id,
                    'channel' => $link->channel,
                    'identifier' => $link->identifier,
                ],
            ]);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'channel' => $schema->string()->description('whatsapp or telegram')->required(),
            'identifier' => $schema->string()->description('WhatsApp digits or numeric Telegram user id, never a display name')->required(),
        ];
    }
}
