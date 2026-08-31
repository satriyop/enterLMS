<?php

namespace App\Http\Requests\Conversation;

use App\Models\Conversation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SetMessagingFocusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->enrollment();
        $course = $this->route('course');
        $lesson = $this->route('lesson');

        if (! $enrollment instanceof Enrollment || ! $lesson instanceof Lesson || ! $course instanceof Course) {
            return false;
        }

        $lesson->loadMissing('section.course');

        if ($lesson->section->course_id !== $course->id) {
            abort(404);
        }

        return Gate::allows('addTurn', [Conversation::class, $enrollment, $lesson]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function enrollment(): ?Enrollment
    {
        $user = $this->user();
        $course = $this->route('course');

        if ($user === null || $course === null) {
            return null;
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
    }
}
