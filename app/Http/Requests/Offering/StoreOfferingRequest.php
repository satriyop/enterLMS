<?php

namespace App\Http\Requests\Offering;

use App\Models\Course;
use App\Models\Offering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Offering::class, $this->route('course')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->id : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::notIn([Offering::DEFAULT_CODE]),
                Rule::unique('offerings', 'code')->where('course_id', $courseId),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'facilitator_id' => ['nullable', 'integer', 'exists:users,id'],
            'facilitator_email' => ['nullable', 'email', 'exists:users,email'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama offering wajib diisi.',
            'name.max' => 'Nama offering maksimal 255 karakter.',
            'code.not_in' => 'Kode default tidak dapat dipakai untuk offering bernama.',
            'code.unique' => 'Kode offering sudah digunakan pada kursus ini.',
            'ends_at.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'facilitator_id.exists' => 'Facilitator yang dipilih tidak valid.',
            'facilitator_email.email' => 'Email facilitator tidak valid.',
            'facilitator_email.exists' => 'Pengguna dengan email itu tidak ditemukan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('facilitator_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        $userId = \App\Models\User::query()->where('email', $email)->value('id');

        if ($userId) {
            $this->merge(['facilitator_id' => $userId]);
        }
    }
}
