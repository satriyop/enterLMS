<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;

beforeEach(function () {
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->course = Course::factory()->published()->create();
    $this->assessment = Assessment::factory()->published()->create([
        'course_id' => $this->course->id,
        'max_attempts' => 3,
    ]);

    // Enroll the learner
    Enrollment::factory()->create([
        'user_id' => $this->learner->id,
        'course_id' => $this->course->id,
        'status' => 'active',
    ]);
});

describe('IP and User Agent Logging', function () {
    it('captures IP address when starting an assessment attempt', function () {
        $response = $this->actingAs($this->learner)
            ->post(route('assessments.start', [$this->course, $this->assessment]));

        $response->assertRedirect();

        $attempt = $this->assessment->attempts()->latest()->first();

        expect($attempt)->not->toBeNull();
        expect($attempt->ip_address)->not->toBeNull();
    });

    it('captures user agent when starting an assessment attempt', function () {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Custom Test Browser';

        $response = $this->actingAs($this->learner)
            ->withHeaders(['User-Agent' => $userAgent])
            ->post(route('assessments.start', [$this->course, $this->assessment]));

        $response->assertRedirect();

        $attempt = $this->assessment->attempts()->latest()->first();

        expect($attempt)->not->toBeNull();
        expect($attempt->user_agent)->toBe($userAgent);
    });

    it('stores IP address in IPv4 format', function () {
        $response = $this->actingAs($this->learner)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->post(route('assessments.start', [$this->course, $this->assessment]));

        $response->assertRedirect();

        $attempt = $this->assessment->attempts()->latest()->first();

        expect($attempt->ip_address)->toMatch('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/');
    });

    it('stores IP address in IPv6 format', function () {
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $response = $this->actingAs($this->learner)
            ->withServerVariables(['REMOTE_ADDR' => $ipv6])
            ->post(route('assessments.start', [$this->course, $this->assessment]));

        $response->assertRedirect();

        $attempt = $this->assessment->attempts()->latest()->first();

        expect($attempt->ip_address)->toBe($ipv6);
    });
});

describe('Anti-cheating data in model', function () {
    it('includes ip_address and user_agent in fillable', function () {
        $attempt = new \App\Models\AssessmentAttempt;

        expect($attempt->getFillable())->toContain('ip_address');
        expect($attempt->getFillable())->toContain('user_agent');
    });
});
