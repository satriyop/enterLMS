<?php

namespace App\Domain\Assessment\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Question;
use Illuminate\Support\Facades\Log;

class AssessmentSubmissionService
{
    public function __construct(
        protected GradingStrategyResolver $gradingResolver,
        protected GradeProposer $gradeProposer,
    ) {}

    public function submitAttempt(AssessmentAttempt $attempt, array $answers, Assessment $assessment): array
    {
        $totalScore = 0;
        $maxScore = $assessment->total_points;
        $hasManualGrading = false;

        // Batch-load all questions upfront to avoid N+1 queries
        $questionIds = array_column($answers, 'question_id');
        $questions = $assessment->questions()->whereIn('id', $questionIds)->get()->keyBy('id');

        foreach ($answers as $answerData) {
            $question = $questions->get($answerData['question_id']);

            if (! $question instanceof Question) {
                Log::warning('Assessment submission: answer for non-existent question skipped', [
                    'question_id' => $answerData['question_id'],
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $assessment->id,
                ]);

                continue;
            }

            $filePath = $this->handleFileUpload($answerData);
            $answerText = $question->formatAnswerForStorage($answerData);

            $answer = $attempt->answers()->create([
                'question_id' => $question->id,
                'answer_text' => $answerText,
                'file_path' => $filePath,
            ]);

            if ($question->requiresManualGrading()) {
                $hasManualGrading = true;
                $this->gradeProposer->propose($answer);

                continue;
            }

            $answerValue = $question->extractAnswerValue($answerData);
            $result = $this->gradeQuestion($question, $answerValue);

            if (($result->metadata['requires_manual_grading'] ?? false) === true) {
                $hasManualGrading = true;
                $this->gradeProposer->propose($answer);

                continue;
            }

            $answer->update([
                'is_correct' => $result->isCorrect,
                'score' => $result->score,
                'feedback' => $result->feedback,
                'proposal_status' => null,
            ]);

            $totalScore += $result->score;
        }

        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= $assessment->passing_score;
        $status = $hasManualGrading ? 'submitted' : 'graded';

        $attempt->update([
            'status' => $status,
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'submitted_at' => now(),
        ]);

        return [
            'totalScore' => $totalScore,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'status' => $status,
        ];
    }

    public function submitBulkGrades(AssessmentAttempt $attempt, array $grades, Assessment $assessment): array
    {
        // Log previous scores when re-grading an already graded attempt
        if ($attempt->isGraded()) {
            Log::info('Assessment re-grading: overwriting previous grades', [
                'attempt_id' => $attempt->id,
                'assessment_id' => $assessment->id,
                'previous_score' => $attempt->score,
                'previous_percentage' => $attempt->percentage,
                'previous_passed' => $attempt->passed,
                'previous_graded_by' => $attempt->graded_by,
                'previous_graded_at' => $attempt->graded_at instanceof \DateTimeInterface ? $attempt->graded_at->format('c') : $attempt->graded_at,
                'regraded_by' => auth()->id(),
            ]);
        }

        // Batch-load all answers upfront to avoid N+1 queries
        $answerIds = array_column($grades, 'answer_id');
        $answersMap = $attempt->answers()->whereIn('id', $answerIds)->get()->keyBy('id');

        foreach ($grades as $gradeData) {
            $answer = $answersMap->get($gradeData['answer_id']);

            if ($answer) {
                $answer->update([
                    'score' => $gradeData['score'],
                    'feedback' => $gradeData['feedback'] ?? null,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                    'proposal_status' => $answer->proposal_status ? 'accepted' : $answer->proposal_status,
                ]);
            }
        }

        return $this->recalculateAttemptTotals($attempt, $assessment, markGraded: true);
    }

    /**
     * @return array{totalScore: float|int, maxScore: float|int, percentage: float, passed: bool}
     */
    public function recalculateAttemptTotals(AssessmentAttempt $attempt, Assessment $assessment, bool $markGraded = false): array
    {
        $totalScore = (float) $attempt->answers()->sum('score');
        $maxScore = $assessment->total_points;
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= $assessment->passing_score;

        $waitingOnProposal = $attempt->answers()
            ->whereIn('proposal_status', ['pending', 'rejected'])
            ->exists();

        $payload = [
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
        ];

        if ($markGraded && ! $waitingOnProposal) {
            $payload['status'] = 'graded';
            $payload['graded_at'] = now();
            $payload['graded_by'] = auth()->id();
        }

        $attempt->update($payload);

        return [
            'totalScore' => $totalScore,
            'maxScore' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
        ];
    }

    public function gradeQuestion(Question $question, mixed $answer): \App\Domain\Assessment\DTOs\GradingResult
    {
        $strategy = $this->gradingResolver->resolve($question);

        if ($strategy === null) {
            return \App\Domain\Assessment\DTOs\GradingResult::partial(
                score: 0,
                maxScore: $question->points,
                feedback: 'Tipe soal tidak didukung untuk penilaian otomatis.',
                metadata: ['requires_manual_grading' => true]
            );
        }

        return $strategy->grade($question, $answer);
    }

    protected function handleFileUpload(array $answerData): ?string
    {
        if (! isset($answerData['file'])) {
            return null;
        }

        return $answerData['file']->store('assessment_answers', 'public');
    }
}
