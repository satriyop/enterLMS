<?php

namespace App\Domain\Assessment\Services;

use App\Models\AttemptAnswer;
use App\Models\Question;

class GradeProposer
{
    public function propose(AttemptAnswer $answer): AttemptAnswer
    {
        $answer->loadMissing('question');
        $question = $answer->question;
        $points = $question instanceof Question ? (float) $question->points : 0.0;
        $text = trim((string) $answer->answer_text);

        $score = $text === '' ? 0.0 : round($points * 0.7, 2);
        $feedback = $text === ''
            ? 'Tidak ada jawaban. Usulan skor 0.'
            : 'Usulan penilaian otomatis berdasarkan jawaban Learner. LMS Admin harus menerima atau menolak.';

        $answer->update([
            'proposal_score' => $score,
            'proposal_feedback' => $feedback,
            'proposal_status' => 'pending',
        ]);

        return $answer->fresh();
    }

    public function accept(AttemptAnswer $answer, ?float $score = null, ?string $feedback = null): AttemptAnswer
    {
        $score ??= (float) $answer->proposal_score;
        $feedback ??= $answer->proposal_feedback;

        $question = $answer->question;
        $max = $question instanceof Question ? (float) $question->points : 0.0;

        $answer->update([
            'score' => $score,
            'feedback' => $feedback,
            'is_correct' => $score >= $max,
            'graded_by' => auth()->id(),
            'graded_at' => now(),
            'proposal_status' => 'accepted',
        ]);

        return $answer->fresh();
    }

    public function reject(AttemptAnswer $answer): AttemptAnswer
    {
        $answer->update([
            'proposal_status' => 'rejected',
        ]);

        return $answer->fresh();
    }
}
