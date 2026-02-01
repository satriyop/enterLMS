<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Category::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
            'description.max' => 'Deskripsi maksimal 1000 karakter.',
            'parent_id.exists' => 'Kategori induk tidak ditemukan.',
            'order.min' => 'Urutan tidak boleh kurang dari 0.',
        ];
    }
}
