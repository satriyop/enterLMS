<?php

use App\Models\QuestionBankItem;
use App\Models\User;

beforeEach(function () {
    $this->trainer = User::factory()->trainer()->create();
    $this->contentManager = User::factory()->contentManager()->create();
    $this->lmsAdmin = User::factory()->lmsAdmin()->create();
    $this->learner = User::factory()->learner()->create();
});

describe('viewAny policy', function () {
    it('allows trainers to view any', function () {
        expect($this->trainer->can('viewAny', QuestionBankItem::class))->toBeTrue();
    });

    it('allows content managers to view any', function () {
        expect($this->contentManager->can('viewAny', QuestionBankItem::class))->toBeTrue();
    });

    it('allows lms admins to view any', function () {
        expect($this->lmsAdmin->can('viewAny', QuestionBankItem::class))->toBeTrue();
    });

    it('denies learners to view any', function () {
        expect($this->learner->can('viewAny', QuestionBankItem::class))->toBeFalse();
    });
});

describe('view policy', function () {
    it('allows owner to view private item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create();

        expect($this->trainer->can('view', $item))->toBeTrue();
    });

    it('denies non-owner to view private item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create();

        expect($this->contentManager->can('view', $item))->toBeFalse();
    });

    it('allows trainers to view department items', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->department()
            ->create();

        expect($this->contentManager->can('view', $item))->toBeTrue();
    });

    it('allows all content creators to view public items', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->public()
            ->create();

        expect($this->contentManager->can('view', $item))->toBeTrue();
        expect($this->lmsAdmin->can('view', $item))->toBeTrue();
    });

    it('denies learners to view any items', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->public()
            ->create();

        expect($this->learner->can('view', $item))->toBeFalse();
    });
});

describe('create policy', function () {
    it('allows trainers to create', function () {
        expect($this->trainer->can('create', QuestionBankItem::class))->toBeTrue();
    });

    it('allows content managers to create', function () {
        expect($this->contentManager->can('create', QuestionBankItem::class))->toBeTrue();
    });

    it('allows lms admins to create', function () {
        expect($this->lmsAdmin->can('create', QuestionBankItem::class))->toBeTrue();
    });

    it('denies learners to create', function () {
        expect($this->learner->can('create', QuestionBankItem::class))->toBeFalse();
    });
});

describe('update policy', function () {
    it('allows owner to update', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->trainer->can('update', $item))->toBeTrue();
    });

    it('allows lms admin to update any item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->lmsAdmin->can('update', $item))->toBeTrue();
    });

    it('denies non-owner to update', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->contentManager->can('update', $item))->toBeFalse();
    });
});

describe('delete policy', function () {
    it('allows owner to delete', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->trainer->can('delete', $item))->toBeTrue();
    });

    it('allows lms admin to delete any item', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->lmsAdmin->can('delete', $item))->toBeTrue();
    });

    it('denies non-owner to delete', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->contentManager->can('delete', $item))->toBeFalse();
    });
});

describe('duplicate policy', function () {
    it('allows duplicating if can view', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->public()
            ->create();

        expect($this->contentManager->can('duplicate', $item))->toBeTrue();
    });

    it('denies duplicating private items from others', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create();

        expect($this->contentManager->can('duplicate', $item))->toBeFalse();
    });
});

describe('import policy', function () {
    it('allows importing if can view', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->department()
            ->create();

        expect($this->contentManager->can('import', $item))->toBeTrue();
    });

    it('denies importing private items from others', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->private()
            ->create();

        expect($this->contentManager->can('import', $item))->toBeFalse();
    });
});

describe('changeVisibility policy', function () {
    it('allows owner to change visibility', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->trainer->can('changeVisibility', $item))->toBeTrue();
    });

    it('allows lms admin to change visibility', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->lmsAdmin->can('changeVisibility', $item))->toBeTrue();
    });

    it('denies non-owner to change visibility', function () {
        $item = QuestionBankItem::factory()
            ->for($this->trainer)
            ->create();

        expect($this->contentManager->can('changeVisibility', $item))->toBeFalse();
    });
});
