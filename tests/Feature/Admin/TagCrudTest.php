<?php

use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

// =============================================================================
// Authorization
// =============================================================================

describe('Tag Authorization', function () {
    it('redirects guests to login', function () {
        $this->get(route('admin.tags.index'))->assertRedirect('/login');
    });

    it('allows any authenticated user to view tags', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)->get(route('admin.tags.index'))
            ->assertOk();
    });

    it('allows content managers to create tags', function () {
        $user = User::factory()->create(['role' => 'content_manager']);

        $this->actingAs($user)->get(route('admin.tags.create'))
            ->assertOk();
    });

    it('forbids learners from creating tags', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)->get(route('admin.tags.create'))
            ->assertForbidden();
    });

    it('forbids content managers from updating tags', function () {
        $user = User::factory()->create(['role' => 'content_manager']);
        $tag = Tag::factory()->create();

        $this->actingAs($user)->put(route('admin.tags.update', $tag), [
            'name' => 'Updated',
            'slug' => 'updated',
        ])->assertForbidden();
    });

    it('forbids content managers from deleting tags', function () {
        $user = User::factory()->create(['role' => 'content_manager']);
        $tag = Tag::factory()->create();

        $this->actingAs($user)->delete(route('admin.tags.destroy', $tag))
            ->assertForbidden();
    });
});

// =============================================================================
// CRUD Operations
// =============================================================================

describe('Tag CRUD', function () {
    it('renders the index page with tags', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Tag::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.tags.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/tags/Index')
                ->has('tags.data', 3)
                ->has('filters')
            );
    });

    it('filters tags by search', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Tag::factory()->create(['name' => 'Keamanan Siber']);
        Tag::factory()->create(['name' => 'Manajemen Risiko']);

        $this->actingAs($admin)->get(route('admin.tags.index', ['search' => 'Keamanan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('tags.data', 1)
                ->where('tags.data.0.name', 'Keamanan Siber')
            );
    });

    it('renders the create form', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->get(route('admin.tags.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/tags/Edit')
                ->where('tag', null)
            );
    });

    it('stores a new tag', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Anti Pencucian Uang',
            'slug' => 'anti-pencucian-uang',
        ])->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tags', [
            'name' => 'Anti Pencucian Uang',
            'slug' => 'anti-pencucian-uang',
        ]);
    });

    it('renders the edit form with tag data', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $tag = Tag::factory()->create();

        $this->actingAs($admin)->get(route('admin.tags.edit', $tag))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/tags/Edit')
                ->where('tag.id', $tag->id)
            );
    });

    it('updates a tag', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $tag = Tag::factory()->create();

        $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name' => 'Updated Tag',
            'slug' => $tag->slug,
        ])->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
        ]);
    });

    it('deletes a tag without courses', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $tag = Tag::factory()->create();

        $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });

    it('prevents deleting a tag that has courses', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $tag = Tag::factory()->create();
        $course = Course::factory()->create();
        $course->tags()->attach($tag);

        $this->actingAs($admin)->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    });
});

// =============================================================================
// Validation
// =============================================================================

describe('Tag Validation', function () {
    it('requires a name', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'slug' => 'test',
        ])->assertSessionHasErrors('name');
    });

    it('requires a unique slug', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Tag::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($admin)->post(route('admin.tags.store'), [
            'name' => 'Test',
            'slug' => 'existing-slug',
        ])->assertSessionHasErrors('slug');
    });

    it('allows same slug on update for own record', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $tag = Tag::factory()->create(['slug' => 'my-slug']);

        $this->actingAs($admin)->put(route('admin.tags.update', $tag), [
            'name' => 'Updated',
            'slug' => 'my-slug',
        ])->assertRedirect(route('admin.tags.index'));
    });
});
