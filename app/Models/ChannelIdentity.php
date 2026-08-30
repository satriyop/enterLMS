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
}
