<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCourseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('setStatus', $this->route('course'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus salah satu dari: draft, published, atau archived.',
        ];
    }
}
