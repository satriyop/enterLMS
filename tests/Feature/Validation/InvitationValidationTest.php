<?php

use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
    $this->course = Course::factory()->published()->create(['user_id' => $this->contentManager->id]);
});

describe('Store Course Invitation Validation', function () {

    it('requires user_id', function () {
        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'message' => 'Test message',
            ])
            ->assertSessionHasErrors('user_id');
    });

    it('validates user_id exists in users table', function () {
        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => 99999,
            ])
            ->assertSessionHasErrors('user_id');
    });

    it('validates user must be a learner', function () {
        $trainer = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $trainer->id,
            ])
            ->assertSessionHasErrors('user_id');
    });

    it('accepts valid learner user_id', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('user_id');
    });

    it('fails when user is already actively enrolled', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        Enrollment::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionHasErrors('user_id');
    });

    it('fails with correct message when user is already enrolled', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        Enrollment::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionHasErrors(['user_id' => 'Pengguna sudah terdaftar di kursus ini.']);
    });

    it('allows invitation when user has dropped enrollment', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        Enrollment::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => 'dropped',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('user_id');
    });

    it('allows invitation when user has completed enrollment', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        Enrollment::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'status' => 'completed',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('user_id');
    });

    it('fails when user has pending invitation', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        CourseInvitation::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'invited_by' => $this->contentManager->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionHasErrors('user_id');
    });

    it('fails with correct message when user has pending invitation', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        CourseInvitation::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'invited_by' => $this->contentManager->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionHasErrors(['user_id' => 'Pengguna sudah memiliki undangan yang menunggu.']);
    });

    it('allows invitation when previous invitation was accepted', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        CourseInvitation::factory()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'invited_by' => $this->contentManager->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('user_id');
    });

    it('allows invitation when previous invitation was declined', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        CourseInvitation::factory()->declined()->create([
            'user_id' => $learner->id,
            'course_id' => $this->course->id,
            'invited_by' => $this->contentManager->id,
        ]);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('user_id');
    });

    it('validates message is nullable', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('message');
    });

    it('validates message must be string', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
                'message' => ['array'],
            ])
            ->assertSessionHasErrors('message');
    });

    it('validates message max length', function (string $message, bool $shouldFail) {
        $learner = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
                'message' => $message,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('message');
        } else {
            $response->assertSessionDoesntHaveErrors('message');
        }
    })->with([
        'at max (500)' => [str_repeat('a', 500), false],
        'exceeds max (501)' => [str_repeat('a', 501), true],
    ]);

    it('validates expires_at is nullable', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
            ])
            ->assertSessionDoesntHaveErrors('expires_at');
    });

    it('validates expires_at must be a date', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
                'expires_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors('expires_at');
    });

    it('validates expires_at must be after today', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
                'expires_at' => now()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('expires_at');
    });

    it('accepts valid future expires_at date', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.store', $this->course), [
                'user_id' => $learner->id,
                'expires_at' => now()->addWeek()->format('Y-m-d'),
            ])
            ->assertSessionDoesntHaveErrors('expires_at');
    });

});

describe('Bulk Course Invitation Validation', function () {

    it('requires file', function () {
        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'message' => 'Test message',
            ])
            ->assertSessionHasErrors('file');
    });

    it('validates file must be a file', function () {
        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => 'not-a-file',
            ])
            ->assertSessionHasErrors('file');
    });

    it('validates file must be csv or txt mime type', function (string $extension, string $mimeType, bool $shouldFail) {
        $file = UploadedFile::fake()->create("users.{$extension}", 100, $mimeType);

        $response = $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('file');
        } else {
            $response->assertSessionDoesntHaveErrors('file');
        }
    })->with([
        'csv file' => ['csv', 'text/csv', false],
        'txt file' => ['txt', 'text/plain', false],
        'xlsx file' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', true],
        'pdf file' => ['pdf', 'application/pdf', true],
    ]);

    it('validates file max size 2MB', function (int $sizeInKb, bool $shouldFail) {
        $file = UploadedFile::fake()->create('users.csv', $sizeInKb, 'text/csv');

        $response = $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('file');
        } else {
            $response->assertSessionDoesntHaveErrors('file');
        }
    })->with([
        'at max (2048 KB)' => [2048, false],
        'exceeds max (2049 KB)' => [2049, true],
    ]);

    it('accepts valid csv file', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
            ])
            ->assertSessionDoesntHaveErrors('file');
    });

    it('validates message is nullable', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
            ])
            ->assertSessionDoesntHaveErrors('message');
    });

    it('validates message must be string', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
                'message' => ['array'],
            ])
            ->assertSessionHasErrors('message');
    });

    it('validates message max length', function (string $message, bool $shouldFail) {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $response = $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
                'message' => $message,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('message');
        } else {
            $response->assertSessionDoesntHaveErrors('message');
        }
    })->with([
        'at max (500)' => [str_repeat('a', 500), false],
        'exceeds max (501)' => [str_repeat('a', 501), true],
    ]);

    it('validates expires_at is nullable', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
            ])
            ->assertSessionDoesntHaveErrors('expires_at');
    });

    it('validates expires_at must be a date', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
                'expires_at' => 'not-a-date',
            ])
            ->assertSessionHasErrors('expires_at');
    });

    it('validates expires_at must be after today', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
                'expires_at' => now()->subDay()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('expires_at');
    });

    it('accepts valid future expires_at date', function () {
        $file = UploadedFile::fake()->create('users.csv', 100, 'text/csv');

        $this->actingAs($this->contentManager)
            ->post(route('courses.invitations.bulk', $this->course), [
                'file' => $file,
                'expires_at' => now()->addWeek()->format('Y-m-d'),
            ])
            ->assertSessionDoesntHaveErrors('expires_at');
    });

});
