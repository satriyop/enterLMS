<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Tutor\ResolveChannelTool;
use App\Models\ChannelIdentity;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('shows the channel identity settings page', function () {
    $user = User::factory()->learner()->create();
    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $user->id,
        'identifier' => '6281111111111',
    ]);

    $this->actingAs($user)
        ->get(route('channels.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Channels')
            ->where('whatsapp', '6281111111111')
            ->where('telegram', null)
        );
});

it('requires authentication', function () {
    $this->get(route('channels.edit'))->assertRedirect(route('login'));
});

it('links a WhatsApp number and normalizes 08 to 62', function () {
    $user = User::factory()->learner()->create();

    $this->actingAs($user)
        ->put(route('channels.update', 'whatsapp'), [
            'identifier' => '0812-3456-7890',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('channel_identities', [
        'user_id' => $user->id,
        'channel' => 'whatsapp',
        'identifier' => '6281234567890',
    ]);
});

it('links a Telegram id', function () {
    $user = User::factory()->learner()->create();

    $this->actingAs($user)
        ->put(route('channels.update', 'telegram'), [
            'identifier' => ' 99887766 ',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('channel_identities', [
        'user_id' => $user->id,
        'channel' => 'telegram',
        'identifier' => '99887766',
    ]);
});

it('updates an existing WhatsApp link instead of creating a second row', function () {
    $user = User::factory()->learner()->create();
    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $user->id,
        'identifier' => '6280000000000',
    ]);

    $this->actingAs($user)
        ->put(route('channels.update', 'whatsapp'), [
            'identifier' => '6289999999999',
        ])
        ->assertSessionHasNoErrors();

    expect(ChannelIdentity::query()->where('user_id', $user->id)->where('channel', 'whatsapp')->count())->toBe(1)
        ->and(ChannelIdentity::query()->where('user_id', $user->id)->where('channel', 'whatsapp')->value('identifier'))
        ->toBe('6289999999999');
});

it('refuses a WhatsApp number already linked to another Learner', function () {
    $owner = User::factory()->learner()->create();
    $stranger = User::factory()->learner()->create();

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $owner->id,
        'identifier' => '6281234567890',
    ]);

    $this->actingAs($stranger)
        ->from(route('channels.edit'))
        ->put(route('channels.update', 'whatsapp'), [
            'identifier' => '081234567890',
        ])
        ->assertSessionHasErrors(['identifier' => 'Identitas ini sudah tertaut ke akun lain.']);

    expect(ChannelIdentity::query()->where('user_id', $stranger->id)->exists())->toBeFalse();
});

it('rejects an invalid WhatsApp number in Indonesian', function () {
    $user = User::factory()->learner()->create();

    $this->actingAs($user)
        ->from(route('channels.edit'))
        ->put(route('channels.update', 'whatsapp'), [
            'identifier' => '123',
        ])
        ->assertSessionHasErrors('identifier');

    $errors = session('errors');
    expect($errors->first('identifier'))->toContain('WhatsApp');
});

it('unlinks a channel', function () {
    $user = User::factory()->learner()->create();
    ChannelIdentity::factory()->telegram()->create([
        'user_id' => $user->id,
        'identifier' => '12345',
    ]);

    $this->actingAs($user)
        ->delete(route('channels.destroy', 'telegram'))
        ->assertRedirect();

    expect(ChannelIdentity::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('does not let a Learner unlink someone else', function () {
    $owner = User::factory()->learner()->create();
    $stranger = User::factory()->learner()->create();
    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $owner->id,
        'identifier' => '6281111111111',
    ]);

    $this->actingAs($stranger)
        ->delete(route('channels.destroy', 'whatsapp'))
        ->assertRedirect();

    $this->assertDatabaseHas('channel_identities', [
        'user_id' => $owner->id,
        'identifier' => '6281111111111',
    ]);
});

it('lets resolve find a Learner after they link from settings', function () {
    $learner = User::factory()->learner()->create();

    $this->actingAs($learner)
        ->put(route('channels.update', 'whatsapp'), [
            'identifier' => '081298765432',
        ])
        ->assertSessionHasNoErrors();

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6281298765432',
    ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->where('data.user_id', $learner->id)->etc());
});

it('404s an unknown channel', function () {
    $user = User::factory()->learner()->create();

    $this->actingAs($user)
        ->put('/settings/channels/sms', [
            'identifier' => '123',
        ])
        ->assertNotFound();
});
