<?php

use App\Http\Controllers\ContentProposalController;
use App\Http\Controllers\ConversationTurnController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseInvitationController;
use App\Http\Controllers\CourseOfferingController;
use App\Http\Controllers\CoursePublishController;
use App\Http\Controllers\CourseRatingController;
use App\Http\Controllers\CourseReorderController;
use App\Http\Controllers\CourseSectionController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeProposalController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonPreviewController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MessagingFocusController;
use App\Http\Middleware\EnsureOfferingsEnabled;
use Illuminate\Support\Facades\Route;

// Lesson Preview (accessible to authenticated users)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('courses/{course}/lessons/{lesson}/preview', [LessonPreviewController::class, 'show'])
        ->name('courses.lessons.preview');

    // Lesson View (for enrolled learners)
    Route::get('courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])
        ->name('courses.lessons.show');

    // Lesson Progress (for enrolled learners)
    Route::patch('courses/{course}/lessons/{lesson}/progress', [LessonProgressController::class, 'update'])
        ->name('courses.lessons.progress.update');
    Route::patch('courses/{course}/lessons/{lesson}/progress/media', [LessonProgressController::class, 'updateMedia'])
        ->name('courses.lessons.progress.media');
    Route::post('courses/{course}/lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])
        ->name('courses.lessons.progress.complete');

    Route::get('courses/{course}/lessons/{lesson}/conversation', [ConversationTurnController::class, 'show'])
        ->name('courses.lessons.conversation.show');
    Route::post('courses/{course}/lessons/{lesson}/conversation/turns', [ConversationTurnController::class, 'store'])
        ->name('courses.lessons.conversation.turns.store');
    Route::post('courses/{course}/lessons/{lesson}/focus/{skin}', [MessagingFocusController::class, 'store'])
        ->whereIn('skin', ['whatsapp', 'telegram'])
        ->name('courses.lessons.focus.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Course CRUD
    Route::resource('courses', CourseController::class);

    // Trash management (soft-deleted courses)
    Route::post('courses/{course}/restore', [CourseController::class, 'restore'])
        ->withTrashed()
        ->name('courses.restore');
    Route::delete('courses/{course}/force-delete', [CourseController::class, 'forceDelete'])
        ->withTrashed()
        ->name('courses.force-delete');

    // Enrollments
    Route::middleware(EnsureOfferingsEnabled::class)->group(function () {
        Route::get('courses/{course}/offerings', [CourseOfferingController::class, 'index'])
            ->name('courses.offerings.index');
        Route::post('courses/{course}/offerings', [CourseOfferingController::class, 'store'])
            ->name('courses.offerings.store');
        Route::put('courses/{course}/offerings/{offering}', [CourseOfferingController::class, 'update'])
            ->name('courses.offerings.update');
        Route::delete('courses/{course}/offerings/{offering}', [CourseOfferingController::class, 'destroy'])
            ->name('courses.offerings.destroy');
    });

    Route::post('courses/{course}/content-proposals', [ContentProposalController::class, 'store'])
        ->name('courses.content-proposals.store');
    Route::post('courses/{course}/content-proposals/{contentProposal}/accept', [ContentProposalController::class, 'accept'])
        ->name('courses.content-proposals.accept');
    Route::post('courses/{course}/content-proposals/{contentProposal}/reject', [ContentProposalController::class, 'reject'])
        ->name('courses.content-proposals.reject');

    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('courses.enroll');
    Route::post('courses/{course}/reenroll', [EnrollmentController::class, 'reenroll'])
        ->middleware('throttle:10,1')
        ->name('courses.reenroll');
    Route::delete('courses/{course}/unenroll', [EnrollmentController::class, 'destroy'])
        ->middleware('throttle:10,1')
        ->name('courses.unenroll');
    Route::post('courses/{course}/bulk-enroll', [EnrollmentController::class, 'bulkEnroll'])
        ->name('courses.bulk-enroll');

    // Course Ratings
    Route::post('courses/{course}/ratings', [CourseRatingController::class, 'store'])
        ->name('courses.ratings.store');
    Route::patch('courses/{course}/ratings/{rating}', [CourseRatingController::class, 'update'])
        ->name('courses.ratings.update');
    Route::delete('courses/{course}/ratings/{rating}', [CourseRatingController::class, 'destroy'])
        ->name('courses.ratings.destroy');

    // Course Invitations (Learner accept/decline)
    Route::post('invitations/{invitation}/accept', [EnrollmentController::class, 'acceptInvitation'])
        ->name('invitations.accept');
    Route::post('invitations/{invitation}/decline', [EnrollmentController::class, 'declineInvitation'])
        ->name('invitations.decline');

    // Course Invitations (Admin create/manage)
    Route::post('courses/{course}/invitations', [CourseInvitationController::class, 'store'])
        ->name('courses.invitations.store');
    Route::post('courses/{course}/invitations/bulk', [CourseInvitationController::class, 'bulkStore'])
        ->name('courses.invitations.bulk');
    Route::delete('courses/{course}/invitations/{invitation}', [CourseInvitationController::class, 'destroy'])
        ->name('courses.invitations.destroy');

    // Learner Search API (for invitation autocomplete)
    Route::get('api/users/search', [CourseInvitationController::class, 'searchLearners'])
        ->middleware('throttle:30,1')
        ->name('api.users.search');

    // Sections
    Route::post('courses/{course}/sections', [CourseSectionController::class, 'store'])
        ->name('courses.sections.store');
    Route::patch('sections/{section}', [CourseSectionController::class, 'update'])
        ->name('sections.update');
    Route::delete('sections/{section}', [CourseSectionController::class, 'destroy'])
        ->name('sections.destroy');

    // Lessons
    Route::get('sections/{section}/lessons/create', [LessonController::class, 'create'])
        ->name('sections.lessons.create');
    Route::post('sections/{section}/lessons', [LessonController::class, 'store'])
        ->name('sections.lessons.store');
    Route::get('lessons/{lesson}/edit', [LessonController::class, 'edit'])
        ->name('lessons.edit');
    Route::patch('lessons/{lesson}', [LessonController::class, 'update'])
        ->name('lessons.update');
    Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])
        ->name('lessons.destroy');

    // Reordering (AJAX)
    Route::post('courses/{course}/sections/reorder', [CourseReorderController::class, 'sections'])
        ->name('courses.sections.reorder');
    Route::post('sections/{section}/lessons/reorder', [CourseReorderController::class, 'lessons'])
        ->name('sections.lessons.reorder');

    // Publishing
    Route::post('courses/{course}/publish', [CoursePublishController::class, 'publish'])
        ->name('courses.publish');
    Route::post('courses/{course}/unpublish', [CoursePublishController::class, 'unpublish'])
        ->name('courses.unpublish');
    Route::post('courses/{course}/archive', [CoursePublishController::class, 'archive'])
        ->name('courses.archive');
    Route::patch('courses/{course}/status', [CoursePublishController::class, 'updateStatus'])
        ->name('courses.status');
    Route::patch('courses/{course}/visibility', [CoursePublishController::class, 'updateVisibility'])
        ->name('courses.visibility');

    // Duration Re-estimation
    Route::post('courses/{course}/recalculate-duration', [\App\Http\Controllers\CourseDurationController::class, 'recalculate'])
        ->name('courses.recalculate-duration');

    // Media
    Route::post('media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    // Assessments (CRUD + publishing)
    Route::prefix('courses/{course}/assessments')->group(function () {
        Route::get('/', [\App\Http\Controllers\AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('create', [\App\Http\Controllers\AssessmentController::class, 'create'])->name('assessments.create');
        Route::post('/', [\App\Http\Controllers\AssessmentController::class, 'store'])->name('assessments.store');
        Route::get('{assessment}', [\App\Http\Controllers\AssessmentController::class, 'show'])->name('assessments.show');
        Route::get('{assessment}/edit', [\App\Http\Controllers\AssessmentController::class, 'edit'])->name('assessments.edit');
        Route::put('{assessment}', [\App\Http\Controllers\AssessmentController::class, 'update'])->name('assessments.update');
        Route::delete('{assessment}', [\App\Http\Controllers\AssessmentController::class, 'destroy'])->name('assessments.destroy');
        Route::post('{assessment}/publish', [\App\Http\Controllers\AssessmentController::class, 'publish'])->name('assessments.publish');
        Route::post('{assessment}/unpublish', [\App\Http\Controllers\AssessmentController::class, 'unpublish'])->name('assessments.unpublish');
        Route::post('{assessment}/archive', [\App\Http\Controllers\AssessmentController::class, 'archive'])->name('assessments.archive');

        // Assessment Attempts (start, submit, view, grade)
        Route::post('{assessment}/start', [\App\Http\Controllers\AssessmentAttemptController::class, 'start'])->name('assessments.start');
        Route::get('{assessment}/attempts/{attempt}', [\App\Http\Controllers\AssessmentAttemptController::class, 'show'])->name('assessments.attempt');
        Route::post('{assessment}/attempts/{attempt}/submit', [\App\Http\Controllers\AssessmentAttemptController::class, 'submit'])->name('assessments.attempt.submit');
        Route::get('{assessment}/attempts/{attempt}/complete', [\App\Http\Controllers\AssessmentAttemptController::class, 'complete'])->name('assessments.attempt.complete');
        Route::get('{assessment}/attempts/{attempt}/grade', [\App\Http\Controllers\AssessmentAttemptController::class, 'grade'])->name('assessments.grade');
        Route::post('{assessment}/attempts/{attempt}/grade', [\App\Http\Controllers\AssessmentAttemptController::class, 'submitGrade'])->name('assessments.grade.submit');
        Route::post('{assessment}/attempts/{attempt}/answers/{answer}/proposal/accept', [GradeProposalController::class, 'accept'])->name('assessments.grade.proposal.accept');
        Route::post('{assessment}/attempts/{attempt}/answers/{answer}/proposal/reject', [GradeProposalController::class, 'reject'])->name('assessments.grade.proposal.reject');
        Route::post('{assessment}/attempts/{attempt}/answers/{answer}/proposal/repropose', [GradeProposalController::class, 'repropose'])->name('assessments.grade.proposal.repropose');

        // Questions
        Route::get('{assessment}/questions', [\App\Http\Controllers\QuestionController::class, 'index'])->name('assessments.questions.index');
        Route::put('{assessment}/questions', [\App\Http\Controllers\QuestionController::class, 'bulkUpdate'])->name('assessments.questions.bulkUpdate');
        Route::delete('{assessment}/questions/{question}', [\App\Http\Controllers\QuestionController::class, 'destroy'])->name('assessments.questions.destroy');
        Route::post('{assessment}/questions/reorder', [\App\Http\Controllers\QuestionController::class, 'reorder'])->name('assessments.questions.reorder');
    });
});
