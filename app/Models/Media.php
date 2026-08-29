<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    public const CAPTURE_MAX_BYTES = 8 * 1024 * 1024;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'size',
        'duration_seconds',
        'custom_properties',
        'order_column',
    ];

    protected $appends = [
        'url',
        'human_readable_size',
        'duration_formatted',
        'is_video',
        'is_audio',
        'is_document',
    ];

    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
            'size' => 'integer',
            'duration_seconds' => 'integer',
            'order_column' => 'integer',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function getIsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function getIsDocumentAttribute(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    public function storedBodyText(): string
    {
        $properties = $this->customPropertiesArray();
        $stored = $properties['body_text'] ?? null;

        return is_string($stored) ? $stored : '';
    }

    public function captureBody(bool $force = false): void
    {
        if (! $force && $this->storedBodyText() !== '') {
            $this->mergeCaptureMeta('ready');

            return;
        }

        if ($this->mime_type !== 'application/pdf') {
            $this->mergeCaptureMeta('unsupported');

            return;
        }

        $text = $this->extractPdfTextAtWriteTime();
        $this->writeBodyCapture($text, $text === '' ? 'failed' : 'ready');
    }

    public function mergeCaptureMeta(string $status): void
    {
        $properties = $this->customPropertiesArray();
        $properties['body_capture'] = $status;
        $properties['body_captured_at'] = now()->toIso8601String();

        $this->forceFill(['custom_properties' => $properties])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function customPropertiesArray(): array
    {
        $properties = $this->custom_properties ?? [];

        return is_array($properties) ? $properties : [];
    }

    private function writeBodyCapture(string $text, string $status): void
    {
        $properties = $this->customPropertiesArray();
        $properties['body_text'] = $text;
        $properties['body_capture'] = $status;
        $properties['body_captured_at'] = now()->toIso8601String();

        $this->forceFill(['custom_properties' => $properties])->save();
    }

    private function extractPdfTextAtWriteTime(): string
    {
        if ((int) $this->size > self::CAPTURE_MAX_BYTES) {
            return '';
        }

        if ($this->path === '' || ! Storage::disk($this->disk)->exists($this->path)) {
            return '';
        }

        return $this->extractUncompressedTjForGeneratedPdfs(
            (string) Storage::disk($this->disk)->get($this->path)
        );
    }

    private function extractUncompressedTjForGeneratedPdfs(string $binary): string
    {
        $texts = [];
        $offset = 0;
        $length = strlen($binary);

        while (($start = strpos($binary, '(', $offset)) !== false) {
            $i = $start + 1;
            $buffer = '';
            $escaped = false;
            $closed = false;

            while ($i < $length) {
                $char = $binary[$i];

                if ($escaped) {
                    $buffer .= $char;
                    $escaped = false;
                    $i++;

                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    $i++;

                    continue;
                }

                if ($char === ')') {
                    $closed = true;
                    $rest = substr($binary, $i + 1, 8);

                    if (preg_match('/^\s*Tj/', $rest) === 1) {
                        $texts[] = $buffer;
                    }

                    $offset = $i + 1;
                    break;
                }

                $buffer .= $char;
                $i++;
            }

            if (! $closed) {
                break;
            }
        }

        return trim(implode("\n", array_filter($texts, fn (string $text): bool => $text !== '')));
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if (! $this->duration_seconds) {
            return null;
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
