<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('tag'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($this->route('tag'))],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tag wajib diisi.',
            'name.max' => 'Nama tag maksimal 255 karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
        ];
    }
}
