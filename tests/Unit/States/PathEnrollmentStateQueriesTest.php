<?php

use App\Domain\LearningPath\States\ActivePathState;
use App\Domain\LearningPath\States\CompletedPathState;
use App\Domain\LearningPath\States\DroppedPathState;
use App\Models\LearningPathEnrollment;

describe('PathEnrollment State Queries', function () {
    it('returns correct label', function (string $stateClass, string $expectedLabel) {
        $state = new $stateClass(new LearningPathEnrollment);

        expect($state->label())->toBe($expectedLabel);
    })->with([
        'active' => [ActivePathState::class, 'Aktif'],
        'completed' => [CompletedPathState::class, 'Selesai'],
        'dropped' => [DroppedPathState::class, 'Keluar'],
    ]);

    it('returns correct canAccessContent', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new LearningPathEnrollment);

        expect($state->canAccessContent())->toBe($expected);
    })->with([
        'active can access' => [ActivePathState::class, true],
        'completed can access for review' => [CompletedPathState::class, true],
        'dropped cannot access' => [DroppedPathState::class, false],
    ]);

    it('returns correct canTrackProgress', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new LearningPathEnrollment);

        expect($state->canTrackProgress())->toBe($expected);
    })->with([
        'active can track' => [ActivePathState::class, true],
        'completed cannot track' => [CompletedPathState::class, false],
        'dropped cannot track' => [DroppedPathState::class, false],
    ]);

    it('returns correct canUnlockCourses', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new LearningPathEnrollment);

        expect($state->canUnlockCourses())->toBe($expected);
    })->with([
        'active can unlock' => [ActivePathState::class, true],
        'completed cannot unlock' => [CompletedPathState::class, false],
        'dropped cannot unlock' => [DroppedPathState::class, false],
    ]);
});
