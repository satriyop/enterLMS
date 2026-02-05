<?php

namespace App\Http\Requests\Compliance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class AuditReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only lms_admin can access compliance reports
        return Gate::allows('viewComplianceReports');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'event_types' => ['nullable', 'array'],
            'event_types.*' => ['string'],
            'aggregate_types' => ['nullable', 'array'],
            'aggregate_types.*' => ['string'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'start_date.date' => 'Tanggal mulai harus berupa tanggal yang valid.',
            'start_date.before_or_equal' => 'Tanggal mulai harus sebelum atau sama dengan tanggal akhir.',
            'end_date.required' => 'Tanggal akhir wajib diisi.',
            'end_date.date' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'user_id.exists' => 'Pengguna tidak ditemukan.',
            'course_id.exists' => 'Kursus tidak ditemukan.',
        ];
    }
}
