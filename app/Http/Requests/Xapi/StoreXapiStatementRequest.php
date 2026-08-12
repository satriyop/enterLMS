<?php

namespace App\Http\Requests\Xapi;

use Illuminate\Foundation\Http\FormRequest;

class StoreXapiStatementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Actor fields from clients are ignored — controller binds the authenticated user.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verb_id' => ['required', 'string', 'url'],
            'verb_display' => ['required', 'string', 'max:100'],
            'object_id' => ['required', 'string'],
            'object_name' => ['nullable', 'string', 'max:255'],
            'object_type' => ['nullable', 'string', 'max:50'],
            // Intentionally not accepted from client for spoof prevention:
            // actor_id, actor_mbox, actor_name
            'result_score_raw' => ['nullable', 'numeric'],
            'result_score_min' => ['nullable', 'numeric'],
            'result_score_max' => ['nullable', 'numeric'],
            'result_score_scaled' => ['nullable', 'numeric', 'between:-1,1'],
            'result_success' => ['nullable', 'boolean'],
            'result_completion' => ['nullable', 'boolean'],
            'result_duration' => ['nullable', 'string', 'max:100'],
            'context_registration' => ['nullable', 'uuid'],
            'context_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'context_enrollment_id' => ['nullable', 'integer', 'exists:enrollments,id'],
            'context_extensions' => ['nullable', 'array'],
            'source' => ['nullable', 'string', 'in:scorm,native,external'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'verb_id.required' => 'Verb ID (IRI) wajib diisi.',
            'verb_id.url' => 'Verb ID harus berupa URL/IRI yang valid.',
            'verb_display.required' => 'Verb display wajib diisi.',
            'object_id.required' => 'Object ID (IRI) wajib diisi.',
        ];
    }
}
