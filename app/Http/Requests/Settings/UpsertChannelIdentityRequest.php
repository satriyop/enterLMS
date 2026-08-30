<?php

namespace App\Http\Requests\Settings;

use App\Models\ChannelIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertChannelIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $channel = (string) $this->route('channel');

        if (! in_array($channel, ChannelIdentity::channels(), true)) {
            return;
        }

        $this->merge([
            'identifier' => ChannelIdentity::normalize(
                $channel,
                (string) $this->input('identifier', ''),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $channel = (string) $this->route('channel');
        $user = $this->user();

        $existingId = $user === null
            ? null
            : ChannelIdentity::query()
                ->where('user_id', $user->id)
                ->where('channel', $channel)
                ->value('id');

        $unique = Rule::unique('channel_identities', 'identifier')
            ->where(fn ($query) => $query->where('channel', $channel));

        if ($existingId !== null) {
            $unique->ignore($existingId);
        }

        $format = $channel === ChannelIdentity::CHANNEL_WHATSAPP
            ? ['regex:/^62[0-9]{8,13}$/']
            : ['regex:/^[0-9]{5,20}$/'];

        return [
            'identifier' => ['required', 'string', 'max:32', $unique, ...$format],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Identitas wajib diisi.',
            'identifier.unique' => 'Identitas ini sudah tertaut ke akun lain.',
            'identifier.regex' => $this->route('channel') === ChannelIdentity::CHANNEL_WHATSAPP
                ? 'Nomor WhatsApp tidak valid. Gunakan format 08… atau 62…'
                : 'ID Telegram tidak valid. Gunakan angka ID akun.',
        ];
    }
}
