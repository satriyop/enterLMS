<?php

namespace App\Http\Requests\Conversation;

use App\Models\Conversation;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreConversationTurnRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->enrollment();
        $lesson = $this->route('lesson');

        if (! $enrollment instanceof Enrollment || ! $lesson instanceof Lesson) {
            return false;
        }

        $lesson->loadMissing('section.course');

        return Gate::allows('addTurn', [Conversation::class, $enrollment, $lesson]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Pesan wajib diisi.',
            'message.string' => 'Pesan harus berupa teks.',
            'message.max' => 'Pesan maksimal 4000 karakter.',
        ];
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
