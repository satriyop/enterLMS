<?php

namespace App\Http\Requests\User;

use App\Domain\Shared\Academy;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->input('external_id');

        if (is_string($id) && trim($id) === '') {
            $this->merge(['external_id' => null]);
        }

        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'external_id' => ['nullable', 'string', 'max:64', Rule::unique('users', 'external_id')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['learner', 'lms_admin', 'lms_admin', 'lms_admin'])],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $targetUser = $this->route('user');
            $currentUser = $this->user();

            // Prevent changing own role
            if ($targetUser->id === $currentUser->id && $this->input('role') !== $currentUser->role) {
                $validator->errors()->add('role', 'Anda tidak dapat mengubah peran Anda sendiri.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.max' => 'Nama pengguna maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'external_id.unique' => Academy::identityLabel().' sudah terdaftar.',
            'external_id.max' => Academy::identityLabel().' maksimal 64 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran yang dipilih tidak valid.',
        ];
    }
}
