<?php

use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->admin = User::factory()->lmsAdmin()->create();
    $this->learner = User::factory()->learner()->create();
    $this->course = Course::factory()->published()->create();
    $this->service = app(EnrollmentService::class);
});

describe('BulkEnrollmentService', function () {
    describe('bulkEnroll', function () {
        it('enrolls multiple learners successfully', function () {
            Event::fake();
            $learners = User::factory()->learner()->count(3)->create();

            $results = $this->service->bulkEnroll(
                userIds: $learners->pluck('id')->toArray(),
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['success'])->toBe(3);
            expect($results['failed'])->toBe(0);
            expect($results['skipped'])->toBe(0);
            expect($results['errors'])->toBeEmpty();

            Event::assertDispatchedTimes(UserEnrolled::class, 3);
        });

        it('skips non-learner users', function () {
            $learner = User::factory()->learner()->create();
            $trainer = User::factory()->trainer()->create();

            $results = $this->service->bulkEnroll(
                userIds: [$learner->id, $trainer->id],
                courseId: $this->course->id,
                enrolledBy: $this->admin->id
            );

            expect($results['success'])->toBe(1);
            expect($results['skipped'])->toBe(1);
            expect($results['errors'])->toHaveCount(1);
            expect($results['errors'][0])->toContain('Hanya learner');
        });

        it('skips already enrolled users', function () {
            $learners = User::factory()->learner()->count(2)->create();
            Enrollment::factory()->active()->create([
                'user_id' => $learners[0]->id,
                'course_id' => $this->course->id,
            ]);

            $results = $this->service->bulkEnroll(
                userIds: $learners->pluck('id')->toArray(),
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['success'])->toBe(1);
            expect($results['skipped'])->toBe(1);
        });

        it('fails for non-existent users', function () {
            $results = $this->service->bulkEnroll(
                userIds: [99999],
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['failed'])->toBe(1);
            expect($results['errors'][0])->toContain('tidak ditemukan');
        });

        it('stops processing for unpublished course', function () {
            $draftCourse = Course::factory()->draft()->create();
            $learners = User::factory()->learner()->count(3)->create();

            $results = $this->service->bulkEnroll(
                userIds: $learners->pluck('id')->toArray(),
                courseId: $draftCourse->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['failed'])->toBe(1);
            expect($results['success'])->toBe(0);
            expect($results['errors'][0])->toContain('belum dipublikasikan');
        });

        it('stops processing for paid course', function () {
            config()->set('lms.mode', 'commercial');
            config()->set('lms.payment.enabled', true);
            $paidCourse = Course::factory()->published()->create([
                'is_paid' => true,
                'price' => 100000,
            ]);
            $learners = User::factory()->learner()->count(2)->create();

            $results = $this->service->bulkEnroll(
                userIds: $learners->pluck('id')->toArray(),
                courseId: $paidCourse->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['failed'])->toBe(1);
            expect($results['success'])->toBe(0);
            expect($results['errors'][0])->toContain('berbayar');
        });

        it('records invited_by from enrolledBy', function () {
            $learner = User::factory()->learner()->create();

            $results = $this->service->bulkEnroll(
                userIds: [$learner->id],
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            $enrollment = Enrollment::where('user_id', $learner->id)
                ->where('course_id', $this->course->id)
                ->first();

            expect($enrollment->invited_by)->toBe($this->trainer->id);
        });
    });

    describe('bulkEnrollFromCsv', function () {
        it('enrolls users from CSV data', function () {
            $learners = User::factory()->learner()->count(2)->create();

            $csvData = [
                [$learners[0]->email],
                [$learners[1]->email],
            ];

            $results = $this->service->bulkEnrollFromCsv(
                csvData: $csvData,
                emailIndex: 0,
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['success'])->toBe(2);
            expect($results['failed'])->toBe(0);
        });

        it('handles invalid emails in CSV', function () {
            $csvData = [
                ['not-an-email'],
                ['also@invalid'],
            ];

            $results = $this->service->bulkEnrollFromCsv(
                csvData: $csvData,
                emailIndex: 0,
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['failed'])->toBe(2);
            expect($results['errors'][0])->toContain('tidak valid');
        });

        it('handles non-existent users in CSV', function () {
            $csvData = [
                ['doesnotexist@example.com'],
            ];

            $results = $this->service->bulkEnrollFromCsv(
                csvData: $csvData,
                emailIndex: 0,
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['failed'])->toBe(1);
            expect($results['errors'][0])->toContain('tidak ditemukan');
        });

        it('skips empty email rows', function () {
            $learner = User::factory()->learner()->create();

            $csvData = [
                [''],
                [$learner->email],
                ['  '],
            ];

            $results = $this->service->bulkEnrollFromCsv(
                csvData: $csvData,
                emailIndex: 0,
                courseId: $this->course->id,
                enrolledBy: $this->trainer->id
            );

            expect($results['success'])->toBe(1);
        });
    });
});

describe('BulkEnrollmentController', function () {
    it('allows trainer to bulk enroll via user_ids', function () {
        $learners = User::factory()->learner()->count(2)->create();

        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => $learners->pluck('id')->toArray(),
            ]);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success');

        expect(Enrollment::where('course_id', $this->course->id)->count())->toBe(2);
    });

    it('allows lms_admin to bulk enroll', function () {
        $learner = User::factory()->learner()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => [$learner->id],
            ]);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success');
    });

    it('denies learner from bulk enrolling', function () {
        $otherLearner = User::factory()->learner()->create();

        $response = $this->actingAs($this->learner)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => [$otherLearner->id],
            ]);

        $response->assertForbidden();
    });

    it('denies content manager from bulk enrolling', function () {
        $contentManager = User::factory()->contentManager()->create();
        $learner = User::factory()->learner()->create();

        $response = $this->actingAs($contentManager)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => [$learner->id],
            ]);

        $response->assertForbidden();
    });

    it('denies bulk enroll on unpublished course', function () {
        $draftCourse = Course::factory()->draft()->create();
        $learner = User::factory()->learner()->create();

        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $draftCourse), [
                'user_ids' => [$learner->id],
            ]);

        $response->assertForbidden();
    });

    it('processes CSV file upload', function () {
        Storage::fake('local');

        $learners = User::factory()->learner()->count(2)->create();

        $csvContent = "email\n{$learners[0]->email}\n{$learners[1]->email}";
        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success');

        expect(Enrollment::where('course_id', $this->course->id)->count())->toBe(2);
    });

    it('validates file format', function () {
        $file = UploadedFile::fake()->create('users.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    });

    it('validates user_ids exist', function () {
        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => [99999],
            ]);

        $response->assertSessionHasErrors('user_ids.0');
    });

    it('requires file or user_ids', function () {
        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), []);

        $response->assertSessionHasErrors(['file', 'user_ids']);
    });

    it('shows mixed results message', function () {
        $learners = User::factory()->learner()->count(2)->create();
        $trainer = User::factory()->trainer()->create();

        // One will enroll, one will be skipped (already enrolled)
        Enrollment::factory()->active()->create([
            'user_id' => $learners[0]->id,
            'course_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->trainer)
            ->post(route('courses.bulk-enroll', $this->course), [
                'user_ids' => array_merge(
                    $learners->pluck('id')->toArray(),
                    [$trainer->id]  // Will be skipped (not learner)
                ),
            ]);

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success');
    });
});

describe('BulkEnrollment Policy', function () {
    it('allows trainer for published course', function () {
        expect($this->trainer->can('bulkEnroll', $this->course))->toBeTrue();
    });

    it('allows lms_admin for published course', function () {
        expect($this->admin->can('bulkEnroll', $this->course))->toBeTrue();
    });

    it('denies learner', function () {
        expect($this->learner->can('bulkEnroll', $this->course))->toBeFalse();
    });

    it('denies content_manager', function () {
        $contentManager = User::factory()->contentManager()->create();
        expect($contentManager->can('bulkEnroll', $this->course))->toBeFalse();
    });

    it('denies for unpublished course', function () {
        $draftCourse = Course::factory()->draft()->create();
        expect($this->trainer->can('bulkEnroll', $draftCourse))->toBeFalse();
    });

    it('denies compliance_officer', function () {
        $complianceOfficer = User::factory()->complianceOfficer()->create();
        expect($complianceOfficer->can('bulkEnroll', $this->course))->toBeFalse();
    });

    it('denies auditor', function () {
        $auditor = User::factory()->auditor()->create();
        expect($auditor->can('bulkEnroll', $this->course))->toBeFalse();
    });

    it('denies teaching_assistant', function () {
        $ta = User::factory()->teachingAssistant()->create();
        expect($ta->can('bulkEnroll', $this->course))->toBeFalse();
    });
});
