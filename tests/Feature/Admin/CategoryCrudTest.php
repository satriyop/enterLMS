<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

// =============================================================================
// Authorization
// =============================================================================

describe('Category Authorization', function () {
    it('redirects guests to login', function () {
        $this->get(route('admin.categories.index'))->assertRedirect('/login');
    });

    it('allows any authenticated user to view categories', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)->get(route('admin.categories.index'))
            ->assertOk();
    });

    it('allows content managers to create categories', function () {
        $user = User::factory()->create(['role' => 'content_manager']);

        $this->actingAs($user)->get(route('admin.categories.create'))
            ->assertOk();
    });

    it('forbids learners from creating categories', function () {
        $learner = User::factory()->create(['role' => 'learner']);

        $this->actingAs($learner)->get(route('admin.categories.create'))
            ->assertForbidden();
    });

    it('forbids content managers from updating categories', function () {
        $user = User::factory()->create(['role' => 'content_manager']);
        $category = Category::factory()->create();

        $this->actingAs($user)->put(route('admin.categories.update', $category), [
            'name' => 'Updated',
            'slug' => 'updated',
        ])->assertForbidden();
    });

    it('forbids content managers from deleting categories', function () {
        $user = User::factory()->create(['role' => 'content_manager']);
        $category = Category::factory()->create();

        $this->actingAs($user)->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();
    });
});

// =============================================================================
// CRUD Operations
// =============================================================================

describe('Category CRUD', function () {
    it('renders the index page with categories', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Category::factory()->count(3)->create();

        $this->actingAs($admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/categories/Index')
                ->has('categories.data', 3)
                ->has('filters')
            );
    });

    it('filters categories by search', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Category::factory()->create(['name' => 'Keamanan Siber']);
        Category::factory()->create(['name' => 'Manajemen Risiko']);

        $this->actingAs($admin)->get(route('admin.categories.index', ['search' => 'Keamanan']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Keamanan Siber')
            );
    });

    it('renders the create form', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->get(route('admin.categories.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/categories/Edit')
                ->where('category', null)
                ->has('parentCategories')
            );
    });

    it('stores a new category', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Kepatuhan OJK',
            'slug' => 'kepatuhan-ojk',
            'description' => 'Kategori untuk regulasi OJK',
            'order' => 1,
        ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Kepatuhan OJK',
            'slug' => 'kepatuhan-ojk',
        ]);
    });

    it('stores a category with a parent', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $parent = Category::factory()->create(['name' => 'Regulasi']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Anti Pencucian Uang',
            'slug' => 'anti-pencucian-uang',
            'parent_id' => $parent->id,
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Anti Pencucian Uang',
            'parent_id' => $parent->id,
        ]);
    });

    it('renders the edit form with category data', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/categories/Edit')
                ->where('category.id', $category->id)
                ->has('parentCategories')
            );
    });

    it('updates a category', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Updated Name',
            'slug' => $category->slug,
        ])->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    });

    it('deletes a category without courses', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $category = Category::factory()->create();

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('prevents deleting a category that has courses', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $category = Category::factory()->create();
        Course::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    });
});

// =============================================================================
// Validation
// =============================================================================

describe('Category Validation', function () {
    it('requires a name', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'slug' => 'test',
        ])->assertSessionHasErrors('name');
    });

    it('requires a unique slug', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        Category::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Test',
            'slug' => 'existing-slug',
        ])->assertSessionHasErrors('slug');
    });

    it('allows same slug on update for own record', function () {
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $category = Category::factory()->create(['slug' => 'my-slug']);

        $this->actingAs($admin)->put(route('admin.categories.update', $category), [
            'name' => 'Updated',
            'slug' => 'my-slug',
        ])->assertRedirect(route('admin.categories.index'));
    });
});
