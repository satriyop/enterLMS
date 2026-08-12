<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Domain\Agent\Services\AgentTokenService;
use App\Mcp\Servers\EnteraksiAgentServer;
use App\Mcp\Tools\Agent\AgentPingTool;
use App\Models\AgentActionLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects unauthenticated mcp http requests', function () {
    $response = $this->postJson('/mcp/enteraksi', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
        'params' => new stdClass,
    ]);

    $response->assertUnauthorized();
});

it('allows authenticated agent token to list tools over http', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $token = app(AgentTokenService::class)->create($user, 'test-agent', [AgentAbility::PING]);

    $response = $this->withToken($token->plainTextToken)
        ->postJson('/mcp/enteraksi', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
            'params' => new stdClass,
        ]);

    $response->assertSuccessful();
    $response->assertJsonPath('result.tools.0.name', 'agent-ping');
});

it('returns identity from agent-ping with required ability', function () {
    $user = User::factory()->create([
        'role' => 'learner',
        'name' => 'Agent Learner',
        'email' => 'agent-learner@example.com',
    ]);

    Sanctum::actingAs($user, [AgentAbility::PING]);

    EnteraksiAgentServer::tool(AgentPingTool::class)
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($user) {
            $json->where('ok', true)
                ->where('server', 'enteraksi-agent')
                ->where('acting_as.id', $user->id)
                ->where('acting_as.email', 'agent-learner@example.com')
                ->etc();
        });

    expect(AgentActionLog::query()->where('tool', 'agent-ping')->where('status', AgentActionLog::STATUS_SUCCESS)->exists())
        ->toBeTrue();
});

it('denies agent-ping without ability and audits denial', function () {
    $user = User::factory()->create(['role' => 'learner']);

    Sanctum::actingAs($user, [AgentAbility::CATALOG_READ]);

    EnteraksiAgentServer::tool(AgentPingTool::class)
        ->assertSee("ability 'agent:ping'");

    expect(AgentActionLog::query()->where('tool', 'agent-ping')->where('status', AgentActionLog::STATUS_DENIED)->exists())
        ->toBeTrue();
});

it('creates agent token via artisan command', function () {
    $user = User::factory()->create([
        'email' => 'token-owner@example.com',
        'role' => 'learner',
    ]);

    $this->artisan('agent:token', [
        'user' => 'token-owner@example.com',
        '--name' => 'openclaw',
        '--ability' => [AgentAbility::PING],
    ])->assertSuccessful();

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->can(AgentAbility::PING))->toBeTrue();
});

it('rejects unknown abilities when creating token', function () {
    $user = User::factory()->create(['role' => 'learner']);

    expect(fn () => app(AgentTokenService::class)->create($user, 'bad', ['agent:nope']))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});
