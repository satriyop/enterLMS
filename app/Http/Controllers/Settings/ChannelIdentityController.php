<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpsertChannelIdentityRequest;
use App\Models\ChannelIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChannelIdentityController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/Channels', [
            'whatsapp' => $user->channelIdentities()
                ->where('channel', ChannelIdentity::CHANNEL_WHATSAPP)
                ->value('identifier'),
            'telegram' => $user->channelIdentities()
                ->where('channel', ChannelIdentity::CHANNEL_TELEGRAM)
                ->value('identifier'),
        ]);
    }

    public function update(UpsertChannelIdentityRequest $request, string $channel): RedirectResponse
    {
        $this->assertChannel($channel);

        ChannelIdentity::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'channel' => $channel,
            ],
            [
                'identifier' => $request->validated('identifier'),
            ],
        );

        $label = $channel === ChannelIdentity::CHANNEL_WHATSAPP ? 'WhatsApp' : 'Telegram';

        return back()->with('success', $label.' berhasil ditautkan ke akun ini.');
    }

    public function destroy(Request $request, string $channel): RedirectResponse
    {
        $this->assertChannel($channel);

        $request->user()
            ->channelIdentities()
            ->where('channel', $channel)
            ->delete();

        $label = $channel === ChannelIdentity::CHANNEL_WHATSAPP ? 'WhatsApp' : 'Telegram';

        return back()->with('success', 'Tautan '.$label.' dihapus.');
    }

    private function assertChannel(string $channel): void
    {
        abort_unless(in_array($channel, ChannelIdentity::channels(), true), 404);
    }
}
