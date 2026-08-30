<?php

namespace App\Http\Requests\Offering;

use App\Models\Course;
use App\Models\Offering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $offering = $this->route('offering');

        return $offering instanceof Offering && Gate::allows('update', $offering);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $course = $this->route('course');
        $offering = $this->route('offering');
        $courseId = $course instanceof Course ? $course->id : 0;
        $offeringId = $offering instanceof Offering ? $offering->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::notIn([Offering::DEFAULT_CODE]),
                Rule::unique('offerings', 'code')
                    ->where('course_id', $courseId)
                    ->ignore($offeringId),
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
        if (! $this->exists('facilitator_email')) {
            return;
        }

        $email = $this->input('facilitator_email');

        if (! is_string($email) || trim($email) === '') {
            $this->merge(['facilitator_id' => null]);

            return;
        }

        $userId = \App\Models\User::query()->where('email', $email)->value('id');

        if ($userId) {
            $this->merge(['facilitator_id' => $userId]);
        }
    }
}
