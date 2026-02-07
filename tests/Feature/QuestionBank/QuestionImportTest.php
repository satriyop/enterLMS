<?php

use App\Domain\QuestionBank\Services\QuestionImportService;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBankItem;
use App\Models\User;

beforeEach(function () {
    // Use contentManager because AssessmentPolicy::update requires isContentManager or isLmsAdmin
    $this->contentManager = User::factory()->contentManager()->create();
    $this->lmsAdmin = User::factory()->lmsAdmin()->create();
    $this->learner = User::factory()->learner()->create();

    $this->course = Course::factory()
        ->for($this->contentManager, 'user')
        ->published()
        ->create();

    $this->assessment = Assessment::factory()
        ->for($this->course)
        ->for($this->contentManager, 'user')
        ->create();
});

describe('Import Questions Index', function () {
    it('shows import page to assessment owner', function () {
        QuestionBankItem::factory()
            ->for($this->contentManager)
            ->public()
            ->count(3)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->get("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('question-bank/Import')
            ->has('items.data', 3)
            ->has('course')
            ->has('assessment')
        );
    });

    it('denies access to non-owners', function () {
        $otherTrainer = User::factory()->trainer()->create();

        $response = $this->actingAs($otherTrainer)
            ->get("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions");

        $response->assertForbidden();
    });

    it('allows lms admin to access any import page', function () {
        $response = $this->actingAs($this->lmsAdmin)
            ->get("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions");

        $response->assertOk();
    });
});

describe('Import Questions Store', function () {
    it('imports questions from bank to assessment', function () {
        $bankItems = QuestionBankItem::factory()
            ->for($this->contentManager)
            ->multipleChoice()
            ->withOptions(4)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => $bankItems->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify questions were created
        expect($this->assessment->questions()->count())->toBe(3);

        // Verify each question has correct source reference
        $this->assessment->questions->each(function (Question $question) use ($bankItems) {
            expect($question->source_question_bank_item_id)->toBeIn($bankItems->pluck('id')->toArray());
        });

        // Verify options were copied
        $this->assessment->questions->each(function (Question $question) {
            expect($question->options()->count())->toBe(4);
        });

        // Verify times_used was incremented
        $bankItems->each(function (QuestionBankItem $item) {
            $item->refresh();
            expect($item->times_used)->toBe(1);
        });
    });

    it('maintains order when importing', function () {
        $bankItems = QuestionBankItem::factory()
            ->for($this->contentManager)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => $bankItems->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();

        $questions = $this->assessment->questions()->orderBy('order')->get();
        expect($questions[0]->order)->toBe(1);
        expect($questions[1]->order)->toBe(2);
        expect($questions[2]->order)->toBe(3);
    });

    it('appends to existing questions', function () {
        // Create existing questions
        Question::factory()
            ->for($this->assessment)
            ->count(2)
            ->create();

        $bankItem = QuestionBankItem::factory()
            ->for($this->contentManager)
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => [$bankItem->id],
            ]);

        $response->assertRedirect();

        expect($this->assessment->questions()->count())->toBe(3);

        // New question should have order = 3 (after existing 2)
        $newQuestion = $this->assessment->questions()->where('source_question_bank_item_id', $bankItem->id)->first();
        expect($newQuestion->order)->toBe(3);
    });

    it('copies question data correctly', function () {
        $bankItem = QuestionBankItem::factory()
            ->for($this->contentManager)
            ->multipleChoice()
            ->create([
                'question_text' => 'Test question',
                'default_points' => 5,
                'feedback' => 'Test feedback',
                'correct_answer' => null,
                'case_sensitive' => false,
                'grading_rubric' => null,
            ]);

        $bankItem->options()->create([
            'option_text' => 'Option A',
            'is_correct' => true,
            'feedback' => 'Correct!',
            'order' => 0,
        ]);

        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => [$bankItem->id],
            ]);

        $response->assertRedirect();

        $question = $this->assessment->questions()->first();
        expect($question->question_text)->toBe('Test question');
        expect($question->points)->toBe(5);
        expect($question->feedback)->toBe('Test feedback');
        expect($question->source_question_bank_item_id)->toBe($bankItem->id);

        $option = $question->options()->first();
        expect($option->option_text)->toBe('Option A');
        expect($option->is_correct)->toBeTrue();
        expect($option->feedback)->toBe('Correct!');
    });

    it('validates question bank item ids exist', function () {
        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => [99999],
            ]);

        $response->assertSessionHasErrors('question_bank_item_ids.0');
    });

    it('denies import of private items from others', function () {
        $otherTrainer = User::factory()->trainer()->create();
        $privateItem = QuestionBankItem::factory()
            ->for($otherTrainer)
            ->private()
            ->create();

        $response = $this->actingAs($this->contentManager)
            ->post("/courses/{$this->course->id}/assessments/{$this->assessment->id}/import-questions", [
                'question_bank_item_ids' => [$privateItem->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        expect($this->assessment->questions()->count())->toBe(0);
    });
});

describe('QuestionImportService', function () {
    it('returns import result with stats', function () {
        $bankItems = QuestionBankItem::factory()
            ->for($this->contentManager)
            ->count(3)
            ->create(['default_points' => 5]);

        $service = app(QuestionImportService::class);
        $result = $service->importToAssessment($this->assessment, $bankItems->pluck('id')->toArray());

        expect($result->imported_count)->toBe(3);
        expect($result->total_points)->toBe(15);
        expect($result->starting_order)->toBe(1);
        expect($result->wasSuccessful())->toBeTrue();
    });

    it('returns empty result for empty input', function () {
        $service = app(QuestionImportService::class);
        $result = $service->importToAssessment($this->assessment, []);

        expect($result->imported_count)->toBe(0);
        expect($result->wasSuccessful())->toBeFalse();
    });

    it('can export question back to bank', function () {
        $question = Question::factory()
            ->for($this->assessment)
            ->multipleChoice()
            ->create([
                'question_text' => 'Export me',
                'points' => 3,
            ]);

        $question->options()->create([
            'option_text' => 'Answer A',
            'is_correct' => true,
            'order' => 0,
        ]);

        $service = app(QuestionImportService::class);
        $bankItem = $service->exportToBank($question, $this->contentManager->id);

        expect($bankItem->question_text)->toBe('Export me');
        expect($bankItem->default_points)->toBe(3);
        expect($bankItem->user_id)->toBe($this->contentManager->id);
        expect($bankItem->visibility)->toBe('private');
        expect($bankItem->options)->toHaveCount(1);
    });
});
