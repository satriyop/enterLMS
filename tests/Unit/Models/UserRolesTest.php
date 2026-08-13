<?php

use App\Models\User;

describe('User Roles', function () {
    it('defines exactly the two roles CONTEXT.md names', function () {
        expect(User::ROLES)->toBe([
            'learner',
            'lms_admin',
        ]);
    });

    it('identifies learner role', function () {
        $user = User::factory()->learner()->make();

        expect($user->isLearner())->toBeTrue()
            ->and($user->isLmsAdmin())->toBeFalse();
    });

    it('identifies lms admin role', function () {
        $user = User::factory()->lmsAdmin()->make();

        expect($user->isLmsAdmin())->toBeTrue()
            ->and($user->isLearner())->toBeFalse();
    });
});

describe('Capabilities', function () {
    it('grants every staff capability to LMS Admin', function () {
        $user = User::factory()->lmsAdmin()->make();

        expect($user->canManageCourses())->toBeTrue()
            ->and($user->canManageLearningPaths())->toBeTrue()
            ->and($user->canViewCompliance())->toBeTrue()
            ->and($user->canGradeAssessments())->toBeTrue();
    });

    it('grants no staff capability to a learner', function () {
        $user = User::factory()->learner()->make();

        expect($user->canManageCourses())->toBeFalse()
            ->and($user->canManageLearningPaths())->toBeFalse()
            ->and($user->canViewCompliance())->toBeFalse()
            ->and($user->canGradeAssessments())->toBeFalse();
    });
});

describe('hasRole()', function () {
    it('matches a single role', function () {
        $user = User::factory()->lmsAdmin()->make();

        expect($user->hasRole('lms_admin'))->toBeTrue()
            ->and($user->hasRole('learner'))->toBeFalse();
    });

    it('matches any role in a list', function () {
        $user = User::factory()->learner()->make();

        expect($user->hasRole(['learner', 'lms_admin']))->toBeTrue()
            ->and($user->hasRole(['lms_admin']))->toBeFalse();
    });
});
