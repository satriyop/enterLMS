<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('redirects guests to login from courses index', function () {
    $this->get('/courses')->assertRedirect('/login');
});

it('allows learners to access courses index', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Browse')
            ->has('courses')
            ->has('categories')
            ->has('filters')
        );
});

it('allows content managers to access courses index', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $this->actingAs($user)->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('courses')
            ->has('categories')
            ->has('filters')
        );
});

it('allows lms admins to access courses index', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($user)->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
        );
});

it('allows content managers to create courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $category = Category::factory()->create();

    $this->actingAs($user)->post('/courses', [
        'title' => 'Test Course',
        'short_description' => 'This is a test course description.',
        'difficulty_level' => 'beginner',
        'visibility' => 'public',
        'category_id' => $category->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'title' => 'Test Course',
        'user_id' => $user->id,
        'status' => 'draft',
    ]);
});

it('allows content managers to update their own courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $user->id]);

    $this->actingAs($user)->put("/courses/{$course->id}", [
        'title' => 'Updated Course Title',
        'short_description' => $course->short_description,
        'difficulty_level' => $course->difficulty_level,
        'visibility' => $course->visibility,
    ])->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Updated Course Title',
    ]);
});

it('forbids content managers from updating others courses', function () {
    $owner = User::factory()->create(['role' => 'content_manager']);
    $other = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $owner->id]);

    $this->actingAs($other)->put("/courses/{$course->id}", [
        'title' => 'Hijacked Title',
        'short_description' => $course->short_description,
        'difficulty_level' => $course->difficulty_level,
        'visibility' => $course->visibility,
    ])->assertForbidden();
});

it('allows lms admins to update any course', function () {
    $owner = User::factory()->create(['role' => 'content_manager']);
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $owner->id]);

    $this->actingAs($admin)->put("/courses/{$course->id}", [
        'title' => 'Admin Updated Title',
        'short_description' => $course->short_description,
        'difficulty_level' => $course->difficulty_level,
        'visibility' => $course->visibility,
    ])->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'title' => 'Admin Updated Title',
    ]);
});

it('forbids content managers from publishing courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post("/courses/{$course->id}/publish")
        ->assertForbidden();
});

it('allows lms admins to publish courses', function () {
    $owner = User::factory()->create(['role' => 'content_manager']);
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['user_id' => $owner->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    Lesson::factory()->create(['course_section_id' => $section->id]);

    $this->actingAs($admin)->post("/courses/{$course->id}/publish")
        ->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'status' => 'published',
        'published_by' => $admin->id,
    ]);
});

it('allows lms admins to unpublish courses', function () {
    $owner = User::factory()->create(['role' => 'content_manager']);
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->create(['user_id' => $owner->id]);

    $this->actingAs($admin)->post("/courses/{$course->id}/unpublish")
        ->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'status' => 'draft',
    ]);
});

it('allows content managers to delete their own draft courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $user->id]);

    $this->actingAs($user)->delete("/courses/{$course->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('forbids content managers from deleting published courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->published()->create(['user_id' => $user->id]);

    $this->actingAs($user)->delete("/courses/{$course->id}")
        ->assertForbidden();
});

it('allows lms admins to delete any course', function () {
    $owner = User::factory()->create(['role' => 'content_manager']);
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->create(['user_id' => $owner->id]);

    $this->actingAs($admin)->delete("/courses/{$course->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('allows courses to have sections and lessons', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->create(['user_id' => $user->id]);
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);

    expect($course->sections)->toHaveCount(1);
    expect($section->lessons)->toHaveCount(1);
    expect($course->sections->first()->lessons->first()->title)->toBe($lesson->title);
});

it('allows courses to have tags', function () {
    $course = Course::factory()->create();
    $tags = Tag::factory()->count(3)->create();
    $course->tags()->attach($tags);

    expect($course->tags)->toHaveCount(3);
});

it('filters courses index by status', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    Course::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draft Course']);
    Course::factory()->published()->create(['user_id' => $user->id, 'title' => 'Published Course']);

    $this->actingAs($user)->get('/courses?status=draft')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('filters', fn (AssertableInertia $filters) => $filters
                ->where('status', 'draft')
                ->etc()
            )
            ->where('courses.data.0.title', 'Draft Course')
            ->has('courses.data', 1)
        );
});

it('filters courses index by search', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    Course::factory()->create(['user_id' => $user->id, 'title' => 'Laravel Course']);
    Course::factory()->create(['user_id' => $user->id, 'title' => 'Python Course']);

    $this->actingAs($user)->get('/courses?search=Laravel')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('filters', fn (AssertableInertia $filters) => $filters
                ->where('search', 'Laravel')
                ->etc()
            )
            ->where('courses.data.0.title', 'Laravel Course')
            ->has('courses.data', 1)
        );
});
