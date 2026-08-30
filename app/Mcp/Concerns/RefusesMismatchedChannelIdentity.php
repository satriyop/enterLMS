<?php

namespace App\Mcp\Concerns;

use App\Models\ChannelIdentity;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Response;

trait RefusesMismatchedChannelIdentity
{
    /**
     * @return array<string, mixed>
     */
    protected function channelIdentityRules(): array
    {
        return [
            'channel' => ['nullable', 'string', Rule::in(ChannelIdentity::channels())],
            'identifier' => ['nullable', 'required_with:channel', 'string', 'max:191'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function channelIdentityMessages(): array
    {
        return [
            'identifier.required_with' => 'identifier wajib diisi bersama channel.',
            'channel.in' => 'channel harus whatsapp atau telegram.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function refuseMismatchedChannel(array $validated): ?Response
    {
        $channel = $validated['channel'] ?? null;
        $identifier = $validated['identifier'] ?? null;

        if (! is_string($channel) || $channel === '' || ! is_string($identifier) || $identifier === '') {
            return null;
        }

        $link = ChannelIdentity::query()
            ->where('channel', $channel)
            ->where('identifier', $identifier)
            ->first();

        if ($link === null || $link->user_id !== (int) $validated['user_id']) {
            return Response::error('user_id tidak sesuai dengan identitas kanal ini.');
        }

        return null;
    }
}
