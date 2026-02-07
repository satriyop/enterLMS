<?php

namespace App\Http\Requests\QuestionBank;

use App\Models\Assessment;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ImportQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Course $course */
        $course = $this->route('course');
        /** @var Assessment $assessment */
        $assessment = $this->route('assessment');

        // Must be able to update the assessment
        return Gate::allows('update', [$assessment, $course]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_bank_item_ids' => ['required', 'array', 'min:1'],
            'question_bank_item_ids.*' => [
                'integer',
                Rule::exists('question_bank_items', 'id')->whereNull('deleted_at'),
            ],
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
            'question_bank_item_ids.required' => 'Pilih setidaknya satu pertanyaan untuk diimpor.',
            'question_bank_item_ids.min' => 'Pilih setidaknya satu pertanyaan untuk diimpor.',
            'question_bank_item_ids.*.exists' => 'Pertanyaan bank yang dipilih tidak valid.',
        ];
    }
}
