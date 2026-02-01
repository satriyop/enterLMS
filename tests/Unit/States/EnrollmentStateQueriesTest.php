<?php

use App\Domain\Enrollment\States\ActiveState;
use App\Domain\Enrollment\States\CompletedState;
use App\Domain\Enrollment\States\DroppedState;
use App\Models\Enrollment;

describe('Enrollment State Queries', function () {
    it('returns correct label', function (string $stateClass, string $expectedLabel) {
        $state = new $stateClass(new Enrollment);

        expect($state->label())->toBe($expectedLabel);
    })->with([
        'active' => [ActiveState::class, 'Aktif'],
        'completed' => [CompletedState::class, 'Selesai'],
        'dropped' => [DroppedState::class, 'Keluar'],
    ]);

    it('returns correct canAccessContent', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new Enrollment);

        expect($state->canAccessContent())->toBe($expected);
    })->with([
        'active can access' => [ActiveState::class, true],
        'completed can access for review' => [CompletedState::class, true],
        'dropped cannot access' => [DroppedState::class, false],
    ]);

    it('returns correct canTrackProgress', function (string $stateClass, bool $expected) {
        $state = new $stateClass(new Enrollment);

        expect($state->canTrackProgress())->toBe($expected);
    })->with([
        'active can track' => [ActiveState::class, true],
        'completed cannot track' => [CompletedState::class, false],
        'dropped cannot track' => [DroppedState::class, false],
    ]);
});
