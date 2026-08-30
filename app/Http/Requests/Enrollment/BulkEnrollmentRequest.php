<?php

namespace App\Http\Requests\Enrollment;

use App\Domain\Shared\Academy;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BulkEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return Gate::allows('bulkEnroll', $course);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->id : 0;

        return [
            // Either file or user_ids must be provided
            'file' => ['required_without:user_ids', 'nullable', 'file', 'mimes:csv,txt', 'max:2048'],
            'user_ids' => ['required_without:file', 'nullable', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'offering_id' => [
                'nullable',
                'integer',
                Rule::exists('offerings', 'id')->where('course_id', $courseId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required_without' => 'Silakan unggah file CSV atau pilih pengguna.',
            'file.mimes' => 'File harus berformat CSV.',
            'file.max' => 'Ukuran file maksimal 2MB.',
            'user_ids.required_without' => 'Silakan pilih pengguna atau unggah file CSV.',
            'user_ids.min' => 'Pilih minimal 1 pengguna.',
            'user_ids.*.exists' => 'Pengguna tidak ditemukan.',
            'offering_id.exists' => Academy::label('offering').' yang dipilih tidak valid.',
        ];
    }
}
