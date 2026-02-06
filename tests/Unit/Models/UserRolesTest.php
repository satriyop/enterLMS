<?php

use App\Models\User;

describe('User Roles', function () {
    it('has all expected roles defined', function () {
        expect(User::ROLES)->toBe([
            'learner',
            'content_manager',
            'trainer',
            'lms_admin',
            'compliance_officer',
            'auditor',
            'teaching_assistant',
        ]);
    });

    it('identifies learner role', function () {
        $user = User::factory()->learner()->make();

        expect($user->isLearner())->toBeTrue();
        expect($user->isTrainer())->toBeFalse();
        expect($user->canManageCourses())->toBeFalse();
    });

    it('identifies content manager role', function () {
        $user = User::factory()->contentManager()->make();

        expect($user->isContentManager())->toBeTrue();
        expect($user->canManageCourses())->toBeTrue();
        expect($user->canManageLearningPaths())->toBeTrue();
    });

    it('identifies trainer role', function () {
        $user = User::factory()->trainer()->make();

        expect($user->isTrainer())->toBeTrue();
        expect($user->canManageCourses())->toBeTrue();
        expect($user->canGradeAssessments())->toBeTrue();
    });

    it('identifies lms admin role', function () {
        $user = User::factory()->lmsAdmin()->make();

        expect($user->isLmsAdmin())->toBeTrue();
        expect($user->canManageCourses())->toBeTrue();
        expect($user->canViewCompliance())->toBeTrue();
        expect($user->canGradeAssessments())->toBeTrue();
    });

    it('identifies compliance officer role', function () {
        $user = User::factory()->complianceOfficer()->make();

        expect($user->isComplianceOfficer())->toBeTrue();
        expect($user->canViewCompliance())->toBeTrue();
        expect($user->canManageCourses())->toBeFalse();
    });

    it('identifies auditor role', function () {
        $user = User::factory()->auditor()->make();

        expect($user->isAuditor())->toBeTrue();
        expect($user->canViewCompliance())->toBeTrue();
        expect($user->canManageCourses())->toBeFalse();
        expect($user->canGradeAssessments())->toBeFalse();
    });

    it('identifies teaching assistant role', function () {
        $user = User::factory()->teachingAssistant()->make();

        expect($user->isTeachingAssistant())->toBeTrue();
        expect($user->canGradeAssessments())->toBeTrue();
        expect($user->canManageCourses())->toBeFalse();
    });
});

describe('Role Permission Groups', function () {
    it('defines course manager roles correctly', function () {
        expect(User::COURSE_MANAGER_ROLES)->toBe([
            'content_manager',
            'trainer',
            'lms_admin',
        ]);
    });

    it('defines compliance roles correctly', function () {
        expect(User::COMPLIANCE_ROLES)->toBe([
            'lms_admin',
            'compliance_officer',
            'auditor',
        ]);
    });

    it('defines grading roles correctly', function () {
        expect(User::GRADING_ROLES)->toBe([
            'trainer',
            'lms_admin',
            'teaching_assistant',
        ]);
    });
});

describe('hasRole method', function () {
    it('checks single role', function () {
        $user = User::factory()->trainer()->make();

        expect($user->hasRole('trainer'))->toBeTrue();
        expect($user->hasRole('learner'))->toBeFalse();
    });

    it('checks array of roles', function () {
        $user = User::factory()->complianceOfficer()->make();

        expect($user->hasRole(['lms_admin', 'compliance_officer']))->toBeTrue();
        expect($user->hasRole(['learner', 'trainer']))->toBeFalse();
    });
});
