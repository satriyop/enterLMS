<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'lms_admin']);
    $this->course = Course::factory()->draft()->create(['user_id' => $this->user->id]);
});

describe('Assessment Store Validation', function () {

    it('requires title', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'passing_score' => 70,
                'max_attempts' => 3,
                'status' => 'draft',
                'visibility' => 'public',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates passing_score bounds', function (mixed $score, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => $score,
                'max_attempts' => 3,
                'status' => 'draft',
                'visibility' => 'public',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('passing_score');
        } else {
            $response->assertSessionDoesntHaveErrors('passing_score');
        }
    })->with([
        'zero (valid)' => [0, false],
        'fifty (valid)' => [50, false],
        'hundred (valid)' => [100, false],
        'negative' => [-1, true],
        'over hundred' => [101, true],
    ]);

    it('validates max_attempts bounds', function (mixed $attempts, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => 70,
                'max_attempts' => $attempts,
                'status' => 'draft',
                'visibility' => 'public',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('max_attempts');
        } else {
            $response->assertSessionDoesntHaveErrors('max_attempts');
        }
    })->with([
        'one (valid min)' => [1, false],
        'ten (valid max)' => [10, false],
        'zero' => [0, true],
        'eleven' => [11, true],
        'negative' => [-1, true],
    ]);

    it('validates time_limit_minutes bounds', function (mixed $minutes, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => 70,
                'max_attempts' => 3,
                'time_limit_minutes' => $minutes,
                'status' => 'draft',
                'visibility' => 'public',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('time_limit_minutes');
        } else {
            $response->assertSessionDoesntHaveErrors('time_limit_minutes');
        }
    })->with([
        'one minute (valid min)' => [1, false],
        'six hours (valid max)' => [360, false],
        'zero' => [0, true],
        'over six hours' => [361, true],
    ]);

    it('validates status enum', function (string $status, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => 70,
                'max_attempts' => 3,
                'status' => $status,
                'visibility' => 'public',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('status');
        } else {
            $response->assertSessionDoesntHaveErrors('status');
        }
    })->with([
        'draft' => ['draft', false],
        'published' => ['published', false],
        'archived' => ['archived', false],
        'invalid' => ['active', true],
    ]);

    it('validates visibility enum', function (string $visibility, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => 70,
                'max_attempts' => 3,
                'status' => 'draft',
                'visibility' => $visibility,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('visibility');
        } else {
            $response->assertSessionDoesntHaveErrors('visibility');
        }
    })->with([
        'public' => ['public', false],
        'restricted' => ['restricted', false],
        'hidden' => ['hidden', false],
        'invalid' => ['private', true],
    ]);

    it('requires passing_score', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'max_attempts' => 3,
                'status' => 'draft',
                'visibility' => 'public',
            ])
            ->assertSessionHasErrors('passing_score');
    });

    it('requires max_attempts', function () {
        $this->actingAs($this->user)
            ->post(route('assessments.store', $this->course), [
                'title' => 'Test Assessment',
                'passing_score' => 70,
                'status' => 'draft',
                'visibility' => 'public',
            ])
            ->assertSessionHasErrors('max_attempts');
    });

});

describe('Assessment Update Validation', function () {

    it('validates passing_score bounds on update', function () {
        $assessment = Assessment::factory()->draft()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('assessments.update', [$this->course, $assessment]), [
                'passing_score' => 101,
            ])
            ->assertSessionHasErrors('passing_score');
    });

    it('validates max_attempts bounds on update', function () {
        $assessment = Assessment::factory()->draft()->create([
            'course_id' => $this->course->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('assessments.update', [$this->course, $assessment]), [
                'max_attempts' => 0,
            ])
            ->assertSessionHasErrors('max_attempts');
    });

});
