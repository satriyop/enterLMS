<?php

use App\Domain\Agent\Services\AgentWebhookDispatcher;
use App\Domain\Certificate\Services\CertificateService;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\UserEnrolled;
use App\Models\AgentWebhookDelivery;
use App\Models\AgentWebhookEndpoint;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('posts signed payload when enrollment completes', function () {
    Http::fake([
        'https://hooks.example.test/*' => Http::response(['ok' => true], 200),
    ]);

    $endpoint = AgentWebhookEndpoint::query()->create([
        'name' => 'openclaw',
        'url' => 'https://hooks.example.test/enteraksi',
        'secret' => 'test-secret-key',
        'events' => ['enrollment.completed'],
        'is_active' => true,
        'max_attempts' => 2,
    ]);

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    $enrollment->load(['user', 'course']);

    EnrollmentCompleted::dispatch($enrollment);

    Http::assertSent(function ($request) use ($endpoint) {
        if ($request->url() !== $endpoint->url) {
            return false;
        }

        $signature = $request->header('X-Enteraksi-Signature')[0] ?? '';
        $event = $request->header('X-Enteraksi-Event')[0] ?? '';
        $body = $request->body();

        $dispatcher = app(AgentWebhookDispatcher::class);

        return $event === 'enrollment.completed'
            && $dispatcher->verify($body, $endpoint->secret, $signature)
            && str_contains($body, 'enrollment.completed');
    });

    expect(AgentWebhookDelivery::query()->where('status', AgentWebhookDelivery::STATUS_SUCCESS)->count())
        ->toBe(1);
});

it('rejects invalid signature verification', function () {
    $dispatcher = app(AgentWebhookDispatcher::class);
    $body = '{"event":"enrollment.completed"}';
    $secret = 'correct-secret';
    $good = 'sha256='.$dispatcher->sign($body, $secret);

    expect($dispatcher->verify($body, $secret, $good))->toBeTrue()
        ->and($dispatcher->verify($body, $secret, 'sha256=deadbeef'))->toBeFalse()
        ->and($dispatcher->verify($body, 'wrong', $good))->toBeFalse();
});

it('does not deliver to disabled endpoints', function () {
    Http::fake();

    AgentWebhookEndpoint::query()->create([
        'name' => 'disabled',
        'url' => 'https://hooks.example.test/off',
        'secret' => 'secret',
        'events' => ['enrollment.created'],
        'is_active' => false,
        'max_attempts' => 1,
    ]);

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    $enrollment->load(['user', 'course']);

    UserEnrolled::dispatch($enrollment);

    Http::assertNothingSent();
    expect(AgentWebhookDelivery::query()->count())->toBe(0);
});

it('retries then marks dead on persistent failure', function () {
    Http::fake([
        'https://hooks.example.test/fail' => Http::response('nope', 500),
    ]);

    $endpoint = AgentWebhookEndpoint::query()->create([
        'name' => 'flaky',
        'url' => 'https://hooks.example.test/fail',
        'secret' => 'secret',
        'events' => ['enrollment.created'],
        'is_active' => true,
        'max_attempts' => 3,
    ]);

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    $enrollment->load(['user', 'course']);

    UserEnrolled::dispatch($enrollment);

    $delivery = AgentWebhookDelivery::query()->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->status)->toBe(AgentWebhookDelivery::STATUS_DEAD)
        ->and($delivery->attempts)->toBe(3)
        ->and($endpoint->fresh()->last_failure_at)->not->toBeNull();

    Http::assertSentCount(3);
});

it('dispatches certificate.issued when certificate is created', function () {
    Http::fake([
        'https://hooks.example.test/cert' => Http::response(['ok' => true], 200),
    ]);

    AgentWebhookEndpoint::query()->create([
        'name' => 'certs',
        'url' => 'https://hooks.example.test/cert',
        'secret' => 'cert-secret',
        'events' => ['certificate.issued'],
        'is_active' => true,
        'max_attempts' => 1,
    ]);

    $user = User::factory()->learner()->create();
    $course = Course::factory()->published()->create();
    $enrollment = Enrollment::factory()->completed()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'progress_percentage' => 100,
    ]);
    $enrollment->load(['user', 'course']);

    app(CertificateService::class)->issueCourseCompletionCertificate($enrollment);

    Http::assertSent(function ($request) {
        return str_contains($request->body(), 'certificate.issued');
    });

    expect(AgentWebhookDelivery::query()->where('event_name', 'certificate.issued')->where('status', 'success')->exists())
        ->toBeTrue();
});

it('registers and disables webhook via artisan', function () {
    $this->artisan('agent:webhook', [
        'action' => 'register',
        '--name' => 'cli-hook',
        '--url' => 'https://hooks.example.test/cli',
        '--secret' => 'cli-secret',
        '--events' => 'enrollment.completed,certificate.issued',
    ])->assertSuccessful();

    $endpoint = AgentWebhookEndpoint::query()->where('name', 'cli-hook')->first();
    expect($endpoint)->not->toBeNull()
        ->and($endpoint->is_active)->toBeTrue();

    $this->artisan('agent:webhook', [
        'action' => 'disable',
        '--id' => $endpoint->id,
    ])->assertSuccessful();

    expect($endpoint->fresh()->is_active)->toBeFalse();
});
