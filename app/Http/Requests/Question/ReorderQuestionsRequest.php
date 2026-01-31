<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->route('assessment'), $this->route('course')]);
    }

    public function rules(): array
    {
        $assessmentId = $this->route('assessment')->id;

        return [
            'questions' => ['required', 'array'],
            'questions.*.id' => [
                'sometimes',
                'integer',
                'nullable',
                Rule::exists('questions', 'id')->where('assessment_id', $assessmentId),
            ],
            'questions.*.position' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'Daftar pertanyaan wajib diisi.',
            'questions.array' => 'Format daftar pertanyaan tidak valid.',
            'questions.*.id.integer' => 'ID pertanyaan harus berupa angka.',
            'questions.*.position.required' => 'Posisi pertanyaan wajib diisi.',
            'questions.*.position.integer' => 'Posisi harus berupa angka.',
            'questions.*.position.min' => 'Posisi tidak boleh negatif.',
        ];
    }
}
