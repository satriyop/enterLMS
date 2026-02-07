<?php

use App\Models\QuestionBankItem;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->contentManager = User::factory()->contentManager()->create();
    $this->lmsAdmin = User::factory()->lmsAdmin()->create();
    $this->learner = User::factory()->learner()->create();
});

describe('QuestionBank Index', function () {
    it('allows trainers to view question bank index', function () {
        QuestionBankItem::factory()
            ->for($this->trainer)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->trainer)
            ->get('/question-bank');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('question-bank/Index')
            ->has('items.data', 3)
        );
    });

    it('denies learners access to question bank', function () {
        $response = $this->actingAs($this->learner)
            ->get('/question-bank');

        $response->assertForbidden();
    });

    it('shows only visible items based on visibility rules', function () {
        // Private item owned by another trainer - should NOT be visible
        $otherTrainer = User::factory()->trainer()->create();
        QuestionBankItem::factory()
            ->for($otherTrainer)
            ->private()
            ->create(['question_text' => 'Private from other']);

        // Public item - should be visible
        QuestionBankItem::factory()
            ->for($otherTrainer)
            ->public()
            ->create(['question_text' => 'Public item']);

        // Department item - should be visible to trainers
        QuestionBankItem::factory()
            ->for($otherTrainer)
            ->department()
            ->create(['question_text' => 'Department item']);

        // Own private item - should be visible
        QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create(['question_text' => 'My private item']);

        $response = $this->actingAs($this->trainer)
            ->get('/question-bank');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('items.data', 3) // public + department + own private
        );
    });

    it('can filter by question type', function () {
        QuestionBankItem::factory()
            ->for($this->trainer)
            ->multipleChoice()
            ->count(2)
            ->create();

        QuestionBankItem::factory()
            ->for($this->trainer)
            ->essay()
            ->create();

        $response = $this->actingAs($this->trainer)
            ->get('/question-bank?type=multiple_choice');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('items.data', 2)
        );
    });

    it('can filter by difficulty level', function () {
        QuestionBankItem::factory()
            ->for($this->trainer)
            ->beginner()
            ->count(2)
            ->create();

        QuestionBankItem::factory()
            ->for($this->trainer)
            ->advanced()
            ->create();

        $response = $this->actingAs($this->trainer)
            ->get('/question-bank?difficulty=beginner');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('items.data', 2)
        );
    });

    it('can search by question text', function () {
        QuestionBankItem::factory()
            ->for($this->trainer)
            ->create(['question_text' => 'What is Laravel?']);

        QuestionBankItem::factory()
            ->for($this->trainer)
            ->create(['question_text' => 'How does Vue work?']);

        $response = $this->actingAs($this->trainer)
            ->get('/question-bank?search=Laravel');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('items.data', 1)
        );
    });
});

describe('QuestionBank Create', function () {
    it('shows create form to trainers', function () {
        $response = $this->actingAs($this->trainer)
            ->get('/question-bank/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('question-bank/Create')
            ->has('tags')
        );
    });

    it('can create a multiple choice question', function () {
        $response = $this->actingAs($this->trainer)
            ->post('/question-bank', [
                'question_text' => 'What is 2 + 2?',
                'question_type' => 'multiple_choice',
                'default_points' => 5,
                'feedback' => 'Basic arithmetic',
                'difficulty_level' => 'beginner',
                'visibility' => 'private',
                'options' => [
                    ['option_text' => '3', 'is_correct' => false, 'order' => 0],
                    ['option_text' => '4', 'is_correct' => true, 'order' => 1],
                    ['option_text' => '5', 'is_correct' => false, 'order' => 2],
                ],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('question_bank_items', [
            'user_id' => $this->trainer->id,
            'question_text' => 'What is 2 + 2?',
            'question_type' => 'multiple_choice',
            'default_points' => 5,
        ]);

        $item = QuestionBankItem::first();
        expect($item->options)->toHaveCount(3);
        expect($item->options->where('is_correct', true)->first()->option_text)->toBe('4');
    });

    it('can create a question with tags', function () {
        $tag1 = Tag::factory()->create(['name' => 'Math']);
        $tag2 = Tag::factory()->create(['name' => 'Basics']);

        $response = $this->actingAs($this->trainer)
            ->post('/question-bank', [
                'question_text' => 'What is 2 + 2?',
                'question_type' => 'short_answer',
                'default_points' => 1,
                'correct_answer' => '4',
                'visibility' => 'private',
                'tag_ids' => [$tag1->id, $tag2->id],
            ]);

        $response->assertRedirect();

        $item = QuestionBankItem::first();
        expect($item->tags)->toHaveCount(2);
    });

    it('validates required fields', function () {
        $response = $this->actingAs($this->trainer)
            ->post('/question-bank', []);

        $response->assertSessionHasErrors(['question_text', 'question_type', 'default_points', 'visibility']);
    });
});

describe('QuestionBank Update', function () {
    it('allows owner to update their item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->multipleChoice()
            ->create();

        $response = $this->actingAs($this->trainer)
            ->put("/question-bank/{$item->id}", [
                'question_text' => 'Updated question text',
                'question_type' => 'multiple_choice',
                'default_points' => 10,
                'visibility' => 'department',
            ]);

        $response->assertRedirect();

        $item->refresh();
        expect($item->question_text)->toBe('Updated question text');
        expect($item->default_points)->toBe(10);
        expect($item->visibility)->toBe('department');
    });

    it('allows lms admin to update any item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        $response = $this->actingAs($this->lmsAdmin)
            ->put("/question-bank/{$item->id}", [
                'question_text' => 'Admin updated',
                'question_type' => 'multiple_choice',
                'default_points' => 5,
                'visibility' => 'public',
            ]);

        $response->assertRedirect();

        $item->refresh();
        expect($item->question_text)->toBe('Admin updated');
    });

    it('denies non-owner from updating', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->put("/question-bank/{$item->id}", [
                'question_text' => 'Unauthorized update',
                'question_type' => 'multiple_choice',
                'default_points' => 5,
                'visibility' => 'private',
            ]);

        $response->assertForbidden();
    });
});

describe('QuestionBank Delete', function () {
    it('allows owner to delete their item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        $response = $this->actingAs($this->trainer)
            ->delete("/question-bank/{$item->id}");

        $response->assertRedirect('/question-bank');
        $this->assertSoftDeleted($item);
    });

    it('allows lms admin to delete any item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        $response = $this->actingAs($this->lmsAdmin)
            ->delete("/question-bank/{$item->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted($item);
    });

    it('denies non-owner from deleting', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->delete("/question-bank/{$item->id}");

        $response->assertForbidden();
    });
});

describe('QuestionBank Duplicate', function () {
    it('can duplicate a visible question', function () {
        $original = QuestionBankItem::factory()
            ->for($this->trainer)
            ->public()
            ->withOptions(4)
            ->create(['question_text' => 'Original question']);

        $tag = Tag::factory()->create();
        $original->tags()->attach($tag);

        $response = $this->actingAs($this->contentManager)
            ->post("/question-bank/{$original->id}/duplicate");

        $response->assertRedirect();

        $duplicate = QuestionBankItem::where('user_id', $this->contentManager->id)->first();
        expect($duplicate)->not->toBeNull();
        expect($duplicate->question_text)->toContain('Salinan');
        expect($duplicate->visibility)->toBe('private');
        expect($duplicate->times_used)->toBe(0);
        expect($duplicate->options)->toHaveCount(4);
        expect($duplicate->tags)->toHaveCount(1);
    });

    it('denies duplicating private items from others', function () {
        $original = QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->post("/question-bank/{$original->id}/duplicate");

        $response->assertForbidden();
    });
});
