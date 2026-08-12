<?php

use App\Models\Course;
use App\Models\CourseRating;
use App\Models\Enrollment;
use App\Models\User;

// =============================================================================
// SoftDeletes — User
// =============================================================================

it('preserves enrollments when user is soft-deleted', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createEnrolledLearner();

    $user->delete();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
    ]);
});

it('preserves course ratings when user is soft-deleted', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course = Course::factory()->published()->create();
    $rating = CourseRating::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'rating' => 5,
    ]);

    $user->delete();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertDatabaseHas('course_ratings', [
        'id' => $rating->id,
        'deleted_at' => null,
    ]);
});

it('excludes soft-deleted users from normal queries', function () {
    $active = User::factory()->create(['role' => 'learner']);
    $deleted = User::factory()->create(['role' => 'learner']);
    $deleted->delete();

    $users = User::all();

    expect($users->pluck('id'))
        ->toContain($active->id)
        ->not->toContain($deleted->id);
});

it('includes soft-deleted users with withTrashed', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $user->delete();

    expect(User::withTrashed()->find($user->id))->not->toBeNull();
    expect(User::find($user->id))->toBeNull();
});

it('restores a soft-deleted user', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $user->delete();
    $this->assertSoftDeleted('users', ['id' => $user->id]);

    $user->restore();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'deleted_at' => null,
    ]);
    expect(User::find($user->id))->not->toBeNull();
});

// =============================================================================
// Enrollment lifecycle — status drop (no SoftDeletes)
// =============================================================================

it('drops an enrollment without deleting the user or course', function () {
    ['user' => $user, 'course' => $course, 'enrollment' => $enrollment] = createEnrolledLearner();

    $enrollment->drop('integrity-test');

    expect($enrollment->fresh()->isDropped())->toBeTrue();
    $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('courses', ['id' => $course->id]);
    $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
});

it('reactivates a dropped enrollment', function () {
    ['enrollment' => $enrollment] = createEnrolledLearner();
    $enrollment->drop('temp');
    expect($enrollment->fresh()->isDropped())->toBeTrue();

    $enrollment->fresh()->reactivate();

    expect($enrollment->fresh()->isActive())->toBeTrue();
});

it('keeps dropped enrollments visible on user relationship', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $course1 = Course::factory()->published()->create();
    $course2 = Course::factory()->published()->create();

    $active = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course1->id,
        'status' => 'active',
    ]);
    $dropped = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course2->id,
        'status' => 'active',
    ]);
    $dropped->drop('gone');

    $enrollments = $user->enrollments;

    expect($enrollments)->toHaveCount(2);
    expect($enrollments->pluck('id')->all())->toContain($active->id, $dropped->id);
});

// =============================================================================
// SoftDeletes — CourseRating
// =============================================================================

it('excludes soft-deleted ratings from course average', function () {
    $course = Course::factory()->published()->create();

    CourseRating::factory()->create([
        'course_id' => $course->id,
        'rating' => 5,
    ]);
    $deletedRating = CourseRating::factory()->create([
        'course_id' => $course->id,
        'rating' => 1,
    ]);
    $deletedRating->delete();

    $avg = Course::query()
        ->where('id', $course->id)
        ->withAvg('ratings', 'rating')
        ->first()
        ->ratings_avg_rating;

    expect((float) $avg)->toBe(5.0);
});

it('excludes soft-deleted ratings from course rating count', function () {
    $course = Course::factory()->published()->create();

    CourseRating::factory()->create(['course_id' => $course->id]);
    CourseRating::factory()->create(['course_id' => $course->id]);
    $deleted = CourseRating::factory()->create(['course_id' => $course->id]);
    $deleted->delete();

    $count = Course::query()
        ->where('id', $course->id)
        ->withCount('ratings')
        ->first()
        ->ratings_count;

    expect($count)->toBe(2);
});

it('restores a soft-deleted rating and recalculates average', function () {
    $course = Course::factory()->published()->create();

    CourseRating::factory()->create([
        'course_id' => $course->id,
        'rating' => 4,
    ]);
    $deleted = CourseRating::factory()->create([
        'course_id' => $course->id,
        'rating' => 2,
    ]);
    $deleted->delete();

    // Average without deleted rating
    $before = Course::withAvg('ratings', 'rating')->find($course->id)->ratings_avg_rating;
    expect((float) $before)->toBe(4.0);

    // Restore and check recalculated
    $deleted->restore();
    $after = Course::withAvg('ratings', 'rating')->find($course->id)->ratings_avg_rating;
    expect((float) $after)->toBe(3.0);
});
