<?php

namespace App\Http\Requests\QuestionBank;

use App\Models\QuestionBankItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreQuestionBankItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', QuestionBankItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_text' => ['required', 'string', 'max:10000'],
            'question_type' => [
                'required',
                Rule::in(['multiple_choice', 'true_false', 'matching', 'short_answer', 'essay', 'file_upload']),
            ],
            'default_points' => ['required', 'integer', 'min:1', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => ['nullable', 'string', 'max:1000'],
            'case_sensitive' => ['sometimes', 'boolean'],
            'grading_rubric' => ['nullable', 'string', 'max:5000'],
            'difficulty_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'visibility' => ['required', Rule::in(['private', 'department', 'public'])],
            'options' => ['sometimes', 'array'],
            'options.*.option_text' => ['required_with:options', 'string', 'max:1000'],
            'options.*.is_correct' => ['sometimes', 'boolean'],
            'options.*.feedback' => ['nullable', 'string', 'max:1000'],
            'options.*.order' => ['sometimes', 'integer', 'min:0'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question_text.required' => 'Teks pertanyaan wajib diisi.',
            'question_text.max' => 'Teks pertanyaan maksimal 10.000 karakter.',
            'question_type.required' => 'Tipe pertanyaan wajib dipilih.',
            'question_type.in' => 'Tipe pertanyaan tidak valid.',
            'default_points.required' => 'Poin default wajib diisi.',
            'default_points.min' => 'Poin default minimal 1.',
            'default_points.max' => 'Poin default maksimal 100.',
            'visibility.required' => 'Visibilitas wajib dipilih.',
            'visibility.in' => 'Visibilitas tidak valid.',
            'difficulty_level.in' => 'Tingkat kesulitan tidak valid.',
            'options.*.option_text.required_with' => 'Teks opsi wajib diisi.',
            'options.*.option_text.max' => 'Teks opsi maksimal 1.000 karakter.',
            'tag_ids.*.exists' => 'Tag tidak valid.',
        ];
    }
}
