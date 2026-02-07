<?php

namespace App\Http\Requests\Scorm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreScormPackageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', \App\Models\ScormPackage::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxSize = config('lms.scorm.max_upload_size_kb', 256000); // 250MB default

        return [
            'file' => [
                'required',
                'file',
                'mimes:zip',
                "max:{$maxSize}",
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File paket SCORM wajib diunggah.',
            'file.file' => 'Data yang diunggah harus berupa file.',
            'file.mimes' => 'Paket SCORM harus berupa file ZIP.',
            'file.max' => 'Ukuran file paket SCORM maksimal :max KB.',
        ];
    }
}
