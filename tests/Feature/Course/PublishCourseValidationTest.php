<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'lms_admin']);
});

it('rejects publishing empty course without sections', function () {
    $course = Course::factory()->draft()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('courses.publish', $course));

    $response->assertRedirect();
    $response->assertSessionHasErrors('publish');

    // Verify course is still draft
    expect($course->fresh()->isDraft())->toBeTrue();
});

it('rejects publishing course with section but no lessons', function () {
    $course = Course::factory()->draft()->create();
    CourseSection::factory()->create(['course_id' => $course->id]);

    $response = $this->actingAs($this->admin)
        ->post(route('courses.publish', $course));

    $response->assertRedirect();
    $response->assertSessionHasErrors('publish');

    expect($course->fresh()->isDraft())->toBeTrue();
});

it('allows publishing course with section and lesson', function () {
    $course = Course::factory()->draft()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['course_section_id' => $section->id]);

    $response = $this->actingAs($this->admin)
        ->post(route('courses.publish', $course));

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('success');

    expect($course->fresh()->isPublished())->toBeTrue();
});

it('returns indonesian error messages for empty course', function () {
    $course = Course::factory()->draft()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('courses.publish', $course));

    $errors = session('errors')->get('publish');

    expect($errors)->toContain('Kursus harus memiliki minimal 1 bagian (section).');
    expect($errors)->toContain('Kursus harus memiliki minimal 1 pelajaran (lesson).');
});
