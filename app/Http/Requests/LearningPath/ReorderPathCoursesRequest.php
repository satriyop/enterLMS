<?php

namespace App\Http\Requests\LearningPath;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReorderPathCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('reorder', $this->route('learning_path'));
    }

    public function rules(): array
    {
        $learningPathId = $this->route('learning_path')->id;

        return [
            'course_order' => ['required', 'array'],
            'course_order.*.id' => [
                'required',
                Rule::exists('learning_path_course', 'course_id')->where('learning_path_id', $learningPathId),
            ],
            'course_order.*.position' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_order.required' => 'Urutan course wajib diisi.',
            'course_order.array' => 'Format urutan course tidak valid.',
            'course_order.*.id.required' => 'ID course wajib diisi.',
            'course_order.*.id.exists' => 'Course tidak ditemukan.',
            'course_order.*.position.required' => 'Posisi course wajib diisi.',
            'course_order.*.position.integer' => 'Posisi harus berupa angka.',
            'course_order.*.position.min' => 'Posisi tidak boleh negatif.',
        ];
    }
}
