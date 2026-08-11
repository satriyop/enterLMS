<?php

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\FreeFlowDemoSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * End-to-end free-flow journey:
 * register → enroll free course → complete lessons → certificate issued → list + verify.
 */
describe('Free Flow Certificate Journey', function () {

    it('registers, completes free course, and receives a verifiable certificate', function () {
        // Arrange: published free course with lessons (no required assessments)
        $course = createPublishedCourseWithContent(2, 2);
        $course->update([
            'is_paid' => false,
            'price' => null,
            'visibility' => 'public',
            'title' => 'Kursus Gratis Journey Test',
        ]);

        // 1. Register as new learner
        $response = $this->post(route('register.store'), [
            'name' => 'Rina Pratama',
            'email' => 'rina.journey@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $learner = User::query()->where('email', 'rina.journey@example.com')->first();
        expect($learner)->not->toBeNull();
        expect($learner->role)->toBe('learner');

        // 2. Browse course detail
        $this->actingAs($learner)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('courses/Detail')
                ->has('course')
            );

        // 3. Enroll (free course)
        $this->actingAs($learner)
            ->post(route('courses.enroll', $course))
            ->assertRedirect()
            ->assertSessionHas('success');

        $enrollment = Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->first();

        expect($enrollment)->not->toBeNull();
        expect($enrollment->is_active)->toBeTrue();
        expect($enrollment->progress_percentage)->toBe(0);

        // 4. Complete every lesson (triggers EnrollmentCompleted → certificate)
        foreach ($course->lessons as $lesson) {
            $this->actingAs($learner)
                ->get(route('courses.lessons.show', [$course, $lesson]))
                ->assertOk();

            $this->actingAs($learner)
                ->patch(route('courses.lessons.progress.update', [$course, $lesson]), [
                    'current_page' => 1,
                    'total_pages' => 1,
                ])
                ->assertOk();
        }

        $enrollment->refresh();
        expect($enrollment->is_completed)->toBeTrue();
        expect($enrollment->progress_percentage)->toBe(100);
        expect($enrollment->completed_at)->not->toBeNull();

        // 5. Certificate auto-issued
        $certificate = Certificate::query()
            ->where('user_id', $learner->id)
            ->where('enrollment_id', $enrollment->id)
            ->first();

        expect($certificate)->not->toBeNull();
        expect($certificate->status)->toBe(Certificate::STATUS_ACTIVE);
        expect($certificate->certificate_number)->toStartWith('CERT-');
        expect($certificate->verification_code)->not->toBeEmpty();
        expect($certificate->recipient_name)->toBe('Rina Pratama');

        // 6. Learner can list certificates
        $this->actingAs($learner)
            ->get(route('certificates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('certificates/Index')
                ->has('certificates', 1)
            );

        // 7. Public verification page works
        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('certificates/VerifyResult')
                ->where('valid', true)
            );

        // 8. PDF stream authorized for owner
        $this->actingAs($learner)
            ->get(route('certificates.stream', $certificate))
            ->assertOk();
    });

    it('seeds free flow demo course that learners can enroll into', function () {
        $this->seed(FreeFlowDemoSeeder::class);

        $learner = User::query()->where('email', 'learner@enteraksi.test')->first();
        expect($learner)->not->toBeNull();
        expect(Hash::check(FreeFlowDemoSeeder::DEMO_PASSWORD, $learner->password))->toBeTrue();

        $course = \App\Models\Course::query()
            ->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)
            ->first();

        expect($course)->not->toBeNull();
        expect($course->is_paid)->toBeFalse();
        expect($course->isPublished())->toBeTrue();
        expect($course->lessons()->count())->toBeGreaterThanOrEqual(3);

        $this->actingAs($learner)
            ->post(route('courses.enroll', $course))
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->exists())->toBeTrue();
    });

});
