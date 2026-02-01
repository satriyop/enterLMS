<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

// =============================================================================
// Index — Trashed Filter
// =============================================================================

it('allows lms admins to view trashed courses', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create(['title' => 'Deleted Course']);
    $course->delete();

    $this->actingAs($admin)->get('/courses?trashed=1')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Deleted Course')
            ->where('filters.trashed', '1')
        );
});

it('does not show trashed courses in normal index', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    Course::factory()->draft()->create(['title' => 'Active Course']);
    $trashed = Course::factory()->draft()->create(['title' => 'Deleted Course']);
    $trashed->delete();

    $this->actingAs($admin)->get('/courses')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'Active Course')
        );
});

it('ignores trashed filter for non-admin users', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    Course::factory()->draft()->create(['user_id' => $user->id, 'title' => 'My Course']);
    $trashed = Course::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Deleted Course']);
    $trashed->delete();

    $this->actingAs($user)->get('/courses?trashed=1')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('courses/Index')
            ->has('courses.data', 1)
            ->where('courses.data.0.title', 'My Course')
        );
});

// =============================================================================
// Restore
// =============================================================================

it('allows lms admins to restore soft-deleted courses', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create();
    $course->delete();

    $this->actingAs($admin)->post("/courses/{$course->id}/restore")
        ->assertRedirect();

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'deleted_at' => null,
    ]);
});

it('forbids content managers from restoring courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $user->id]);
    $course->delete();

    $this->actingAs($user)->post("/courses/{$course->id}/restore")
        ->assertForbidden();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('forbids learners from restoring courses', function () {
    $learner = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->draft()->create();
    $course->delete();

    $this->actingAs($learner)->post("/courses/{$course->id}/restore")
        ->assertForbidden();
});

// =============================================================================
// Force Delete
// =============================================================================

it('allows lms admins to permanently delete soft-deleted courses', function () {
    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create();
    $course->delete();

    $this->actingAs($admin)->delete("/courses/{$course->id}/force-delete")
        ->assertRedirect();

    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

it('deletes thumbnail when force-deleting a course', function () {
    Storage::fake('public');
    Storage::disk('public')->put('courses/thumbnails/test.jpg', 'dummy');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->draft()->create([
        'thumbnail_path' => 'courses/thumbnails/test.jpg',
    ]);
    $course->delete();

    $this->actingAs($admin)->delete("/courses/{$course->id}/force-delete")
        ->assertRedirect();

    Storage::disk('public')->assertMissing('courses/thumbnails/test.jpg');
    $this->assertDatabaseMissing('courses', ['id' => $course->id]);
});

it('forbids content managers from force-deleting courses', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $course = Course::factory()->draft()->create(['user_id' => $user->id]);
    $course->delete();

    $this->actingAs($user)->delete("/courses/{$course->id}/force-delete")
        ->assertForbidden();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
});

it('forbids learners from force-deleting courses', function () {
    $learner = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->draft()->create();
    $course->delete();

    $this->actingAs($learner)->delete("/courses/{$course->id}/force-delete")
        ->assertForbidden();
});

// =============================================================================
// Soft Delete Preserves Thumbnail
// =============================================================================

it('preserves thumbnail on soft delete', function () {
    Storage::fake('public');
    Storage::disk('public')->put('courses/thumbnails/keep.jpg', 'dummy');

    $admin = User::factory()->create(['role' => 'lms_admin']);
    $course = Course::factory()->published()->create([
        'thumbnail_path' => 'courses/thumbnails/keep.jpg',
    ]);

    $this->actingAs($admin)->delete("/courses/{$course->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('courses', ['id' => $course->id]);
    Storage::disk('public')->assertExists('courses/thumbnails/keep.jpg');
});
