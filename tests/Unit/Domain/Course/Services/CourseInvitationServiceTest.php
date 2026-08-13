<?php

use App\Domain\Course\Services\CourseInvitationService;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\User;

beforeEach(function () {
    $this->service = app(CourseInvitationService::class);
});

describe('CourseInvitationService', function () {
    describe('getExcludedUserIds', function () {
        it('returns user IDs with active enrollments', function () {
            $course = Course::factory()->published()->create();
            $user1 = User::factory()->create(['role' => 'learner']);
            $user2 = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $user1->id,
                'course_id' => $course->id,
            ]);

            Enrollment::factory()->active()->create([
                'user_id' => $user2->id,
                'course_id' => $course->id,
            ]);

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toContain($user1->id);
            expect($excludedIds)->toContain($user2->id);
            expect(count($excludedIds))->toBe(2);
        });

        it('returns user IDs with pending invitations', function () {
            $course = Course::factory()->published()->create();
            $user1 = User::factory()->create(['role' => 'learner']);
            $user2 = User::factory()->create(['role' => 'learner']);

            CourseInvitation::factory()->pending()->create([
                'user_id' => $user1->id,
                'course_id' => $course->id,
            ]);

            CourseInvitation::factory()->pending()->create([
                'user_id' => $user2->id,
                'course_id' => $course->id,
            ]);

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toContain($user1->id);
            expect($excludedIds)->toContain($user2->id);
            expect(count($excludedIds))->toBe(2);
        });

        it('returns combined list of enrolled users and pending invitations', function () {
            $course = Course::factory()->published()->create();
            $enrolledUser = User::factory()->create(['role' => 'learner']);
            $invitedUser = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $enrolledUser->id,
                'course_id' => $course->id,
            ]);

            CourseInvitation::factory()->pending()->create([
                'user_id' => $invitedUser->id,
                'course_id' => $course->id,
            ]);

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toContain($enrolledUser->id);
            expect($excludedIds)->toContain($invitedUser->id);
            expect(count($excludedIds))->toBe(2);
        });

        it('excludes only active enrollments not dropped ones', function () {
            $course = Course::factory()->published()->create();
            $activeUser = User::factory()->create(['role' => 'learner']);
            $droppedUser = User::factory()->create(['role' => 'learner']);

            Enrollment::factory()->active()->create([
                'user_id' => $activeUser->id,
                'course_id' => $course->id,
            ]);

            Enrollment::factory()->dropped()->create([
                'user_id' => $droppedUser->id,
                'course_id' => $course->id,
            ]);

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toContain($activeUser->id);
            expect($excludedIds)->not->toContain($droppedUser->id);
        });

        it('excludes only pending invitations not accepted or declined', function () {
            $course = Course::factory()->published()->create();
            $pendingUser = User::factory()->create(['role' => 'learner']);
            $acceptedUser = User::factory()->create(['role' => 'learner']);
            $declinedUser = User::factory()->create(['role' => 'learner']);

            CourseInvitation::factory()->pending()->create([
                'user_id' => $pendingUser->id,
                'course_id' => $course->id,
            ]);

            CourseInvitation::factory()->accepted()->create([
                'user_id' => $acceptedUser->id,
                'course_id' => $course->id,
            ]);

            CourseInvitation::factory()->declined()->create([
                'user_id' => $declinedUser->id,
                'course_id' => $course->id,
            ]);

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toContain($pendingUser->id);
            expect($excludedIds)->not->toContain($acceptedUser->id);
            expect($excludedIds)->not->toContain($declinedUser->id);
        });

        it('returns empty array when no enrollments or invitations', function () {
            $course = Course::factory()->published()->create();

            $excludedIds = $this->service->getExcludedUserIds($course);

            expect($excludedIds)->toBeArray();
            expect(count($excludedIds))->toBe(0);
        });
    });

    describe('importFromCsv', function () {
        it('successfully imports valid learners from CSV', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner1 = User::factory()->create([
                'email' => 'learner1@example.com',
                'role' => 'learner',
            ]);
            $learner2 = User::factory()->create([
                'email' => 'learner2@example.com',
                'role' => 'learner',
            ]);

            $csvData = [
                ['learner1@example.com'],
                ['learner2@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(2);
            expect($result['skipped'])->toBe(0);
            expect($result['errors'])->toBeEmpty();

            $invitations = CourseInvitation::where('course_id', $course->id)->get();
            expect($invitations)->toHaveCount(2);
        });

        it('skips non-existent email addresses', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create([
                'email' => 'valid@example.com',
                'role' => 'learner',
            ]);

            $csvData = [
                ['valid@example.com'],
                ['nonexistent@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(1);
            expect($result['skipped'])->toBe(1);
            expect($result['errors'])->toHaveCount(1);
            expect($result['errors'][0])->toContain('tidak ditemukan');
        });

        it('skips users who are not learners', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $trainer = User::factory()->create([
                'email' => 'trainer@example.com',
                'role' => 'lms_admin',
            ]);

            $csvData = [
                ['trainer@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(0);
            expect($result['skipped'])->toBe(1);
            expect($result['errors'][0])->toContain('bukan learner');
        });

        it('skips already enrolled users', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create([
                'email' => 'enrolled@example.com',
                'role' => 'learner',
            ]);

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $csvData = [
                ['enrolled@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(0);
            expect($result['skipped'])->toBe(1);
            expect($result['errors'][0])->toContain('sudah terdaftar');
        });

        it('skips users with pending invitations', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create([
                'email' => 'invited@example.com',
                'role' => 'learner',
            ]);

            CourseInvitation::factory()->pending()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $csvData = [
                ['invited@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(0);
            expect($result['skipped'])->toBe(1);
            expect($result['errors'][0])->toContain('memiliki undangan');
        });

        it('creates invitations with message and expiration', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create([
                'email' => 'learner@example.com',
                'role' => 'learner',
            ]);

            $csvData = [
                ['learner@example.com'],
            ];

            $message = 'Selamat datang di kursus kami!';
            $expiresAt = now()->addWeek();

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id,
                $message,
                $expiresAt
            );

            expect($result['success'])->toBe(1);

            $invitation = CourseInvitation::where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->first();

            expect($invitation->message)->toBe($message);
            expect($invitation->expires_at->timestamp)->toBe($expiresAt->timestamp);
        });

        it('returns empty result when course does not exist', function () {
            $inviter = User::factory()->create(['role' => 'lms_admin']);

            $csvData = [
                ['learner@example.com'],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                99999,
                $inviter->id
            );

            expect($result['success'])->toBe(0);
            expect($result['skipped'])->toBe(0);
            expect($result['errors'])->toBeEmpty();
        });

        it('batch loads users to avoid N+1 queries', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);

            $learners = User::factory()->count(10)->create(['role' => 'learner']);

            $csvData = $learners->map(fn ($learner) => [$learner->email])->toArray();

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(10);
            expect($result['skipped'])->toBe(0);
        });

        it('skips empty rows in CSV', function () {
            $course = Course::factory()->published()->create();
            $inviter = User::factory()->create(['role' => 'lms_admin']);
            $learner = User::factory()->create([
                'email' => 'learner@example.com',
                'role' => 'learner',
            ]);

            $csvData = [
                ['learner@example.com'],
                [],
                [''],
            ];

            $result = $this->service->importFromCsv(
                $csvData,
                0,
                $course->id,
                $inviter->id
            );

            expect($result['success'])->toBe(1);
            expect($result['skipped'])->toBe(0);
        });
    });

    describe('canInvite', function () {
        it('allows invitation for eligible learner', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            $result = $this->service->canInvite($learner, $course);

            expect($result['can'])->toBeTrue();
            expect($result)->not->toHaveKey('reason');
        });

        it('rejects non-learner users', function () {
            $trainer = User::factory()->create(['role' => 'lms_admin']);
            $course = Course::factory()->published()->create();

            $result = $this->service->canInvite($trainer, $course);

            expect($result['can'])->toBeFalse();
            expect($result['reason'])->toContain('Hanya learner');
        });

        it('rejects already enrolled users', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            Enrollment::factory()->active()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $result = $this->service->canInvite($learner, $course);

            expect($result['can'])->toBeFalse();
            expect($result['reason'])->toContain('sudah terdaftar');
        });

        it('rejects users with pending invitations', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            CourseInvitation::factory()->pending()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $result = $this->service->canInvite($learner, $course);

            expect($result['can'])->toBeFalse();
            expect($result['reason'])->toContain('undangan tertunda');
        });

        it('allows invitation for dropped enrollment', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            Enrollment::factory()->dropped()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $result = $this->service->canInvite($learner, $course);

            expect($result['can'])->toBeTrue();
        });

        it('allows invitation for declined invitation', function () {
            $learner = User::factory()->create(['role' => 'learner']);
            $course = Course::factory()->published()->create();

            CourseInvitation::factory()->declined()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ]);

            $result = $this->service->canInvite($learner, $course);

            expect($result['can'])->toBeTrue();
        });
    });
});
