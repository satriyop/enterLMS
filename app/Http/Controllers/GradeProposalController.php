<?php

namespace App\Http\Controllers;

use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Assessment\Services\AssessmentSubmissionService;
use App\Domain\Assessment\Services\GradeProposer;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeProposalController extends Controller
{
    public function __construct(
        protected GradeProposer $proposer,
        protected AssessmentSubmissionService $submissionService,
    ) {}

    public function accept(Request $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt, AttemptAnswer $answer): RedirectResponse
    {
        Gate::authorize('grade', [$attempt, $assessment, $course]);
        $this->assertAnswerOnAttempt($attempt, $answer);

        $this->proposer->accept($answer);
        $assessment->loadSum('questions', 'points');
        $this->submissionService->recalculateAttemptTotals($attempt->fresh(), $assessment, markGraded: true);

        $freshAttempt = $attempt->fresh(['assessment']);
        $freshAttempt->assessment->loadSum('questions', 'points');

        if ($freshAttempt->status === 'graded') {
            AssessmentGraded::dispatch($freshAttempt);
        }

        return back()->with('success', 'Usulan penilaian diterima.');
    }

    public function reject(Request $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt, AttemptAnswer $answer): RedirectResponse
    {
        Gate::authorize('grade', [$attempt, $assessment, $course]);
        $this->assertAnswerOnAttempt($attempt, $answer);

        $this->proposer->reject($answer);

        return back()->with('success', 'Usulan penilaian ditolak. Percobaan masih menunggu penilaian.');
    }

    public function repropose(Request $request, Course $course, Assessment $assessment, AssessmentAttempt $attempt, AttemptAnswer $answer): RedirectResponse
    {
        Gate::authorize('grade', [$attempt, $assessment, $course]);
        $this->assertAnswerOnAttempt($attempt, $answer);

        $this->proposer->propose($answer);

        return back()->with('success', 'Usulan penilaian baru dibuat.');
    }

    private function assertAnswerOnAttempt(AssessmentAttempt $attempt, AttemptAnswer $answer): void
    {
        if ($answer->assessment_attempt_id !== $attempt->id) {
            abort(404);
        }
    }
}
