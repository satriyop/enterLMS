<?php

namespace App\Http\Requests\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Tag::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug'],
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
