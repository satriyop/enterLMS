<?php

namespace App\Http\Requests\Section;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderSectionLessonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('section')->course);
    }

    public function rules(): array
    {
        $sectionId = $this->route('section')->id;

        return [
            'lessons' => ['required', 'array'],
            'lessons.*' => [
                'required',
                'integer',
                Rule::exists('lessons', 'id')->where('course_section_id', $sectionId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'lessons.required' => 'Daftar lesson wajib diisi.',
            'lessons.array' => 'Format daftar lesson tidak valid.',
            'lessons.*.required' => 'ID lesson wajib diisi.',
            'lessons.*.integer' => 'ID lesson harus berupa angka.',
            'lessons.*.exists' => 'Lesson tidak ditemukan.',
        ];
    }
}
