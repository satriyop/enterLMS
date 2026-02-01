<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization for questions delegates to the parent assessment's course.
 * Questions are always managed in the context of their assessment.
 */
class QuestionPolicy
{
    /**
     * Determine whether the user can view any questions for an assessment.
     */
    public function viewAny(User $user, Assessment $assessment): bool
    {
        return Gate::allows('update', [$assessment, $assessment->course]);
    }

    /**
     * Determine whether the user can view the question.
     */
    public function view(User $user, Question $question): bool
    {
        $assessment = $question->assessment;

        return Gate::allows('view', [$assessment, $assessment->course]);
    }

    /**
     * Determine whether the user can create questions for an assessment.
     */
    public function create(User $user, Assessment $assessment): bool
    {
        return Gate::allows('update', [$assessment, $assessment->course]);
    }

    /**
     * Determine whether the user can update the question.
     */
    public function update(User $user, Question $question): bool
    {
        $assessment = $question->assessment;

        return Gate::allows('update', [$assessment, $assessment->course]);
    }

    /**
     * Determine whether the user can delete the question.
     */
    public function delete(User $user, Question $question): bool
    {
        $assessment = $question->assessment;

        return Gate::allows('update', [$assessment, $assessment->course]);
    }

    /**
     * Determine whether the user can reorder questions.
     */
    public function reorder(User $user, Assessment $assessment): bool
    {
        return Gate::allows('update', [$assessment, $assessment->course]);
    }
}
