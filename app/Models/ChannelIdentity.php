<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $channel
 * @property string $identifier
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class ChannelIdentity extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelIdentityFactory> */
    use HasFactory;

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_TELEGRAM = 'telegram';

    /**
     * @return list<string>
     */
    public static function channels(): array
    {
        return [
            self::CHANNEL_WHATSAPP,
            self::CHANNEL_TELEGRAM,
        ];
    }

    protected $fillable = [
        'user_id',
        'channel',
        'identifier',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function normalizeWhatsApp(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    public static function normalizeTelegram(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public static function normalize(string $channel, string $raw): string
    {
        return $channel === self::CHANNEL_WHATSAPP
            ? self::normalizeWhatsApp($raw)
            : self::normalizeTelegram($raw);
    }
}
