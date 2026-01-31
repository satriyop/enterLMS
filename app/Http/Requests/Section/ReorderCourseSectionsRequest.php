<?php

namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderCourseSectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('course'));
    }

    public function rules(): array
    {
        $courseId = $this->route('course')->id;

        return [
            'sections' => ['required', 'array'],
            'sections.*' => [
                'required',
                'integer',
                Rule::exists('course_sections', 'id')->where('course_id', $courseId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sections.required' => 'Daftar section wajib diisi.',
            'sections.array' => 'Format daftar section tidak valid.',
            'sections.*.required' => 'ID section wajib diisi.',
            'sections.*.integer' => 'ID section harus berupa angka.',
            'sections.*.exists' => 'Section tidak ditemukan.',
        ];
    }
}
