<?php

use App\Models\Category;
use App\Models\Tag;

describe('Category Store Validation', function () {

    it('requires name', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'slug' => 'test-category',
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates name max length', function (string $name, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => $name,
                'slug' => 'test-category',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('name');
        } else {
            $response->assertSessionDoesntHaveErrors('name');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('requires slug', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates slug max length', function (string $slug, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => $slug,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('slug');
        } else {
            $response->assertSessionDoesntHaveErrors('slug');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('validates slug is unique', function () {
        $existingCategory = Category::factory()->create(['slug' => 'existing-slug']);

        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'existing-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('accepts unique slug', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'unique-slug',
            ])
            ->assertSessionDoesntHaveErrors('slug');
    });

    it('validates description max length', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'description' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('description');
    });

    it('accepts description at max length', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'description' => str_repeat('a', 1000),
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('accepts null description', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'description' => null,
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('validates parent_id exists', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('accepts valid parent_id', function () {
        $parent = Category::factory()->create();

        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'parent_id' => $parent->id,
            ])
            ->assertSessionDoesntHaveErrors('parent_id');
    });

    it('accepts null parent_id', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'parent_id' => null,
            ])
            ->assertSessionDoesntHaveErrors('parent_id');
    });

    it('validates order is non-negative', function (int $order, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'order' => $order,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('order');
        } else {
            $response->assertSessionDoesntHaveErrors('order');
        }
    })->with([
        'zero' => [0, false],
        'positive' => [100, false],
        'negative' => [-1, true],
    ]);

    it('accepts null order', function () {
        asAdmin()
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'order' => null,
            ])
            ->assertSessionDoesntHaveErrors('order');
    });

});

describe('Category Update Validation', function () {

    it('requires name on update', function () {
        $category = Category::factory()->create();

        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => '',
                'slug' => 'test-category',
            ])
            ->assertSessionHasErrors('name');
    });

    it('requires slug on update', function () {
        $category = Category::factory()->create();

        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Category',
                'slug' => '',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates slug is unique on update (ignores current record)', function () {
        $category = Category::factory()->create(['slug' => 'my-slug']);
        $otherCategory = Category::factory()->create(['slug' => 'other-slug']);

        // Should fail - slug exists on another record
        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Category',
                'slug' => 'other-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('accepts same slug on update (ignores current record)', function () {
        $category = Category::factory()->create(['slug' => 'my-slug']);

        // Should pass - using own slug
        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Updated Name',
                'slug' => 'my-slug',
            ])
            ->assertSessionDoesntHaveErrors('slug');
    });

    it('validates description max length on update', function () {
        $category = Category::factory()->create();

        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'description' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('description');
    });

    it('validates parent_id exists on update', function () {
        $category = Category::factory()->create();

        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'parent_id' => 99999,
            ])
            ->assertSessionHasErrors('parent_id');
    });

    it('validates order is non-negative on update', function () {
        $category = Category::factory()->create();

        asAdmin()
            ->put(route('admin.categories.update', $category), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'order' => -1,
            ])
            ->assertSessionHasErrors('order');
    });

});

describe('Tag Store Validation', function () {

    it('requires name', function () {
        asAdmin()
            ->post(route('admin.tags.store'), [
                'slug' => 'test-tag',
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates name max length', function (string $name, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.tags.store'), [
                'name' => $name,
                'slug' => 'test-tag',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('name');
        } else {
            $response->assertSessionDoesntHaveErrors('name');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('requires slug', function () {
        asAdmin()
            ->post(route('admin.tags.store'), [
                'name' => 'Test Tag',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates slug max length', function (string $slug, bool $shouldFail) {
        $response = asAdmin()
            ->post(route('admin.tags.store'), [
                'name' => 'Test Tag',
                'slug' => $slug,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('slug');
        } else {
            $response->assertSessionDoesntHaveErrors('slug');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('validates slug is unique', function () {
        $existingTag = Tag::factory()->create(['slug' => 'existing-slug']);

        asAdmin()
            ->post(route('admin.tags.store'), [
                'name' => 'Test Tag',
                'slug' => 'existing-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('accepts unique slug', function () {
        asAdmin()
            ->post(route('admin.tags.store'), [
                'name' => 'Test Tag',
                'slug' => 'unique-slug',
            ])
            ->assertSessionDoesntHaveErrors('slug');
    });

});

describe('Tag Update Validation', function () {

    it('requires name on update', function () {
        $tag = Tag::factory()->create();

        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => '',
                'slug' => 'test-tag',
            ])
            ->assertSessionHasErrors('name');
    });

    it('requires slug on update', function () {
        $tag = Tag::factory()->create();

        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Test Tag',
                'slug' => '',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('validates slug is unique on update (ignores current record)', function () {
        $tag = Tag::factory()->create(['slug' => 'my-slug']);
        $otherTag = Tag::factory()->create(['slug' => 'other-slug']);

        // Should fail - slug exists on another record
        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Test Tag',
                'slug' => 'other-slug',
            ])
            ->assertSessionHasErrors('slug');
    });

    it('accepts same slug on update (ignores current record)', function () {
        $tag = Tag::factory()->create(['slug' => 'my-slug']);

        // Should pass - using own slug
        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Updated Name',
                'slug' => 'my-slug',
            ])
            ->assertSessionDoesntHaveErrors('slug');
    });

    it('validates name max length on update', function () {
        $tag = Tag::factory()->create();

        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => str_repeat('a', 256),
                'slug' => 'test-tag',
            ])
            ->assertSessionHasErrors('name');
    });

    it('validates slug max length on update', function () {
        $tag = Tag::factory()->create();

        asAdmin()
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Test Tag',
                'slug' => str_repeat('a', 256),
            ])
            ->assertSessionHasErrors('slug');
    });

});
