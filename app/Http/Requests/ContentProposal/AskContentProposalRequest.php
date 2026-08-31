<?php

namespace App\Http\Requests\ContentProposal;

use App\Models\ContentProposal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AskContentProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return Gate::allows('create', [ContentProposal::class, $course]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $course = $this->route('course');

        return [
            'lesson_id' => [
                'required',
                'integer',
                Rule::exists('lessons', 'id')->where(function ($query) use ($course) {
                    $query->whereIn('course_section_id', function ($sections) use ($course) {
                        $sections->select('id')
                            ->from('course_sections')
                            ->where('course_id', $course->id)
                            ->whereNull('deleted_at');
                    });
                }),
            ],
            'instruction' => ['required', 'string', 'min:8', 'max:4000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'Pelajaran wajib dipilih.',
            'lesson_id.exists' => 'Pelajaran tidak termasuk dalam Course ini.',
            'instruction.required' => 'Instruksi untuk Author Agent wajib diisi.',
            'instruction.min' => 'Instruksi terlalu singkat.',
            'instruction.max' => 'Instruksi maksimal 4000 karakter.',
        ];
    }
}
