<?php

namespace App\Http\Resources;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for question options.
 *
 * Used in: QuestionResource
 *
 * SECURITY: The is_correct field is hidden when learners are taking an in-progress attempt.
 *
 * @mixin QuestionOption
 */
class QuestionOptionResource extends JsonResource
{
    /**
     * Disable wrapping for Inertia compatibility.
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_text' => $this->option_text,
            'order' => $this->order,
            'feedback' => $this->feedback,
            'is_correct' => $this->when(
                $this->shouldShowCorrectAnswer($request),
                $this->is_correct
            ),
        ];
    }

    /**
     * Determine if the correct answer should be shown to the current user.
     *
     * Show is_correct ONLY when:
     * - User can manage courses (instructors/admins), OR
     * - The attempt is submitted/graded (not in_progress)
     */
    protected function shouldShowCorrectAnswer(Request $request): bool
    {
        $user = $request->user();

        // Always show to instructors/admins
        if ($user?->canManageCourses()) {
            return true;
        }

        // Check if we're on the attempt page with in_progress status
        $routeName = $request->route()?->getName();
        if ($routeName === 'assessments.attempt') {
            // Get attempt from route
            $attempt = $request->route('attempt');
            if ($attempt && $attempt->status === 'in_progress') {
                return false;
            }
        }

        // Show on all other pages (edit, grade, completed attempt)
        return true;
    }
}
