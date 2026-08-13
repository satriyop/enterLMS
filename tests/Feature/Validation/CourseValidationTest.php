<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'lms_admin']);
});

describe('Course Store Validation', function () {

    it('requires title', function () {
        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'difficulty_level' => 'beginner',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length', function (string $title, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => $title,
                'difficulty_level' => 'beginner',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('title');
        } else {
            $response->assertSessionDoesntHaveErrors('title');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('requires difficulty_level', function () {
        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
            ])
            ->assertSessionHasErrors('difficulty_level');
    });

    it('validates difficulty_level enum', function (string $level, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => $level,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('difficulty_level');
        } else {
            $response->assertSessionDoesntHaveErrors('difficulty_level');
        }
    })->with([
        'beginner' => ['beginner', false],
        'intermediate' => ['intermediate', false],
        'advanced' => ['advanced', false],
        'invalid value' => ['expert', true],
        'empty string' => ['', true],
    ]);

    it('validates category_id exists', function () {
        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'category_id' => 99999,
            ])
            ->assertSessionHasErrors('category_id');
    });

    it('accepts valid category_id', function () {
        $category = Category::factory()->create();

        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'category_id' => $category->id,
            ])
            ->assertSessionDoesntHaveErrors('category_id');
    });

    it('validates tags exist', function () {
        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'tags' => [99999],
            ])
            ->assertSessionHasErrors('tags.0');
    });

    it('accepts valid tags', function () {
        $tag = Tag::factory()->create();

        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'tags' => [$tag->id],
            ])
            ->assertSessionDoesntHaveErrors('tags.0');
    });

    it('validates short_description max length', function () {
        $this->actingAs($this->user)
            ->post(route('courses.store'), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'short_description' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('short_description');
    });

});

describe('Course Update Validation', function () {

    it('requires title on update', function () {
        $course = Course::factory()->draft()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch(route('courses.update', $course), [
                'title' => '',
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
            ])
            ->assertSessionHasErrors('title');
    });

    it('requires visibility on update', function () {
        $course = Course::factory()->draft()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch(route('courses.update', $course), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
            ])
            ->assertSessionHasErrors('visibility');
    });

    it('validates visibility enum', function (string $visibility, bool $shouldFail) {
        $course = Course::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('courses.update', $course), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
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

    it('validates manual_duration_minutes is positive', function () {
        $course = Course::factory()->draft()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patch(route('courses.update', $course), [
                'title' => 'Test Course',
                'difficulty_level' => 'beginner',
                'visibility' => 'public',
                'manual_duration_minutes' => 0,
            ])
            ->assertSessionHasErrors('manual_duration_minutes');
    });

});
