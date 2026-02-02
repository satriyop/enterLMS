<?php

namespace App\Http\Controllers;

use App\Domain\Assessment\Events\AssessmentAttemptStarted;
use App\Domain\Assessment\Events\AssessmentAttemptSubmitted;
use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Assessment\Exceptions\MaxAttemptsReachedException;
use App\Domain\Assessment\Services\AssessmentSubmissionService;
use App\Http\Requests\Assessment\BulkGradeAnswersRequest;
use App\Http\Requests\Assessment\SubmitAssessmentAnswersRequest;
use App\Http\Resources\AssessmentAttemptResource;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentAttemptController extends Controller
{
    public function __construct(
        protected AssessmentSubmissionService $submissionService
    ) {}

    /**
     * Start a new assessment attempt.
     */
    public function start(Request $request, Course $course, Assessment $assessment): RedirectResponse
    {
        Gate::authorize('attempt', [$assessment, $course]);

        $user = $request->user();

        try {
            $assessment->validateAttemptOrFail($user);
        } catch (MaxAttemptsReachedException $e) {
            return back()->with('error', sprintf(
                'Anda telah mencapai batas maksimal percobaan (%d/%d) untuk penilaian ini.',
                $e->getContext()['completed_attempts'],
                $e->getContext()['max_attempts']
            ));
        }

        $nextAttemptNumber = $assessment->attempts()->where('user_id', $user->id)->max('attempt_number') + 1;

        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'attempt_number' => $nextAttemptNumber,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        AssessmentAttemptStarted::dispatch($attempt);

        return redirect()
            ->route('assessments.attempt', [$course, $assessment, $attempt])
            ->with('success', 'Penilaian dimulai. Silakan jawab semua pertanyaan.');
    }

    /**
     * Show assessment attempt page.
     */
    public function show(Request $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt): Response
    {
        Gate::authorize('viewAttempt', [$attempt, $assessment, $course]);

        $assessment->load(['questions.options']);
        $attempt->load(['answers']);

        return Inertia::render('assessments/Attempt', [
            'course' => $course,
            'assessment' => new AssessmentResource($assessment),
            'attempt' => new AssessmentAttemptResource($attempt),
            'can' => [
                'submit' => $attempt->isInProgress(),
            ],
        ]);
    }

    /**
     * Submit assessment attempt.
     */
    public function submit(SubmitAssessmentAnswersRequest $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt): RedirectResponse
    {
        Gate::authorize('submitAttempt', [$attempt, $assessment, $course]);

        if (! $attempt->isInProgress()) {
            return back()->with('error', 'Penilaian ini tidak dapat diserahkan.');
        }

        $validated = $request->validated();

        $assessment->loadSum('questions', 'points');
        $this->submissionService->submitAttempt($attempt, $validated['answers'], $assessment);

        AssessmentAttemptSubmitted::dispatch($attempt->fresh());

        return redirect()
            ->route('assessments.attempt.complete', [$course, $assessment, $attempt])
            ->with('success', 'Penilaian berhasil diserahkan.');
    }

    /**
     * Show attempt completion page.
     */
    public function complete(Course $course, Assessment $assessment, AssessmentAttempt $attempt): Response
    {
        Gate::authorize('viewAttempt', [$attempt, $assessment, $course]);

        $attempt->load(['answers.question']);

        return Inertia::render('assessments/AttemptComplete', [
            'course' => $course,
            'assessment' => new AssessmentResource($assessment),
            'attempt' => new AssessmentAttemptResource($attempt),
        ]);
    }

    /**
     * Show grading page for an assessment attempt.
     */
    public function grade(Course $course, Assessment $assessment, AssessmentAttempt $attempt): Response
    {
        Gate::authorize('grade', [$attempt, $assessment, $course]);

        $assessment->load(['questions.options']);
        $attempt->load(['answers.question', 'user']);

        return Inertia::render('assessments/Grade', [
            'course' => $course,
            'assessment' => new AssessmentResource($assessment),
            'attempt' => new AssessmentAttemptResource($attempt),
            'can' => [
                'submit' => $attempt->isSubmitted(),
            ],
        ]);
    }

    /**
     * Submit grading for an assessment attempt.
     */
    public function submitGrade(BulkGradeAnswersRequest $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt): RedirectResponse
    {
        $validated = $request->validated();

        $assessment->loadSum('questions', 'points');
        $this->submissionService->submitBulkGrades($attempt, $validated['grades'], $assessment);

        AssessmentGraded::dispatch($attempt->fresh());

        return redirect()
            ->route('assessments.grade', [$course, $assessment, $attempt])
            ->with('success', 'Penilaian berhasil disimpan.');
    }
}
