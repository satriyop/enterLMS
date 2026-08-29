<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

class CaptureMediaBodyText extends Command
{
    protected $signature = 'media:capture-body-text
                            {--force : Recapture even when body_text is already stored (destructive to seeder source paragraphs)}
                            {--media= : Capture a single media id}';

    protected $description = 'Capture stored body_text for PDF media at write time (Tutor grounding)';

    public function handle(): int
    {
        $query = Media::query()->where('mime_type', 'application/pdf');
        $mediaId = $this->option('media');

        if ($mediaId !== null && $mediaId !== '') {
            $query->where('id', $mediaId);
        }

        $force = (bool) $this->option('force');
        $count = 0;

        $query->each(function (Media $media) use ($force, &$count): void {
            $media->captureBody($force);
            $count++;
        });

        $this->info("Captured {$count} PDF(s).");

        return self::SUCCESS;
    }
}
