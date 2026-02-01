<?php

use App\Domain\LearningPath\States\AvailableCourseState;
use App\Domain\LearningPath\States\CompletedCourseState;
use App\Domain\LearningPath\States\InProgressCourseState;
use App\Domain\LearningPath\States\LockedCourseState;
use App\Models\LearningPathCourseProgress;

describe('CourseProgress State Queries', function () {
    it('returns correct label', function (string $stateClass, string $expectedLabel) {
        $state = new $stateClass(new LearningPathCourseProgress);

        expect($state->label())->toBe($expectedLabel);
    })->with([
        'locked' => [LockedCourseState::class, 'Terkunci'],
        'available' => [AvailableCourseState::class, 'Tersedia'],
        'in progress' => [InProgressCourseState::class, 'Sedang Berlangsung'],
        'completed' => [CompletedCourseState::class, 'Selesai'],
    ]);

    it('returns correct canStart', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new LearningPathCourseProgress);

        expect($state->canStart())->toBe($expected);
    })->with([
        'locked cannot start' => [LockedCourseState::class, false],
        'available can start' => [AvailableCourseState::class, true],
        'in progress can start' => [InProgressCourseState::class, true],
        'completed can start' => [CompletedCourseState::class, true],
    ]);

    it('returns correct blocksNext', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new LearningPathCourseProgress);

        expect($state->blocksNext())->toBe($expected);
    })->with([
        'locked blocks next' => [LockedCourseState::class, true],
        'available blocks next' => [AvailableCourseState::class, true],
        'in progress blocks next' => [InProgressCourseState::class, true],
        'completed does not block next' => [CompletedCourseState::class, false],
    ]);
});
