<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Attaches demo lesson files (PDF generated in PHP, audio/video from fixtures).
 */
class SeederLessonMedia
{
    public function attachFixture(Lesson $lesson, string $collection, string $filename, string $mimeType, ?int $durationSeconds = null): Media
    {
        $source = database_path('seeders/fixtures/'.$filename);

        if (! is_file($source)) {
            throw new RuntimeException("Seed fixture missing: {$source}");
        }

        return $this->store(
            $lesson,
            $collection,
            $filename,
            $mimeType,
            (string) file_get_contents($source),
            $durationSeconds,
        );
    }

    /**
     * @param  list<string>  $paragraphs
     */
    public function attachPdf(Lesson $lesson, string $filename, string $title, array $paragraphs): Media
    {
        return $this->store(
            $lesson,
            'document',
            $filename,
            'application/pdf',
            $this->pdf($title, $paragraphs),
            bodyText: implode("\n\n", $paragraphs),
        );
    }

    /**
     * @param  list<string>  $paragraphs
     */
    public function pdf(string $title, array $paragraphs): string
    {
        $lines = [];
        $lines[] = ['title', $this->pdfSafe($title)];
        $lines[] = ['blank', ''];

        foreach ($paragraphs as $paragraph) {
            foreach ($this->wrap($this->pdfSafe($paragraph), 92) as $line) {
                $lines[] = ['body', $line];
            }
            $lines[] = ['blank', ''];
        }

        $chunks = array_chunk($lines, 38);
        $pageCount = count($chunks);
        $fontObject = 3;
        $firstPageObject = 4;

        $pageObjects = [];
        $contentObjects = [];

        foreach ($chunks as $index => $pageLines) {
            $pageObject = $firstPageObject + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageObjects[] = $pageObject;
            $contentObjects[$pageObject] = $this->pageStream($pageLines);

            unset($contentObject);
        }

        $kids = implode(' ', array_map(fn (int $id) => "{$id} 0 R", $pageObjects));

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => "<< /Type /Pages /Kids [{$kids}] /Count {$pageCount} >>",
            $fontObject => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        foreach ($pageObjects as $pageObject) {
            $contentObject = $pageObject + 1;
            $stream = $contentObjects[$pageObject];
            $length = strlen($stream);
            $objects[$pageObject] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 {$fontObject} 0 R >> >> /Contents {$contentObject} 0 R >>";
            $objects[$contentObject] = "<< /Length {$length} >>\nstream\n{$stream}\nendstream";
        }

        ksort($objects);

        return $this->assemble(array_values($objects));
    }

    /**
     * @param  list<array{0: string, 1: string}>  $lines
     */
    private function pageStream(array $lines): string
    {
        $commands = ['BT', '/F1 16 Tf', '72 720 Td'];
        $first = true;

        foreach ($lines as [$kind, $text]) {
            if (! $first) {
                $commands[] = $kind === 'title' ? '0 -28 Td' : '0 -16 Td';
            }
            $first = false;

            if ($kind === 'blank') {
                continue;
            }

            if ($kind === 'title') {
                $commands[] = '/F1 16 Tf';
            } else {
                $commands[] = '/F1 11 Tf';
            }

            $commands[] = '('.$this->escapePdf($text).') Tj';
        }

        $commands[] = 'ET';

        return implode("\n", $commands);
    }

    /**
     * @param  list<string>  $objects
     */
    private function assemble(array $objects): string
    {
        $header = "%PDF-1.4\n";
        $body = '';
        $offsets = [];

        foreach ($objects as $index => $content) {
            $number = $index + 1;
            $offsets[$number] = strlen($header) + strlen($body);
            $body .= "{$number} 0 obj\n{$content}\nendobj\n";
        }

        $xrefPosition = strlen($header) + strlen($body);
        $size = count($objects) + 1;
        $xref = "xref\n0 {$size}\n0000000000 65535 f\r\n";

        for ($i = 1; $i < $size; $i++) {
            $xref .= sprintf("%010d 00000 n\r\n", $offsets[$i]);
        }

        $trailer = "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF\n";

        return $header.$body.$xref.$trailer;
    }

    /**
     * @return list<string>
     */
    private function wrap(string $text, int $width): array
    {
        $text = trim($text);

        if ($text === '') {
            return [''];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (strlen($candidate) > $width && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function pdfSafe(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $ascii === false ? $text : $ascii;
    }

    private function escapePdf(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function store(
        Lesson $lesson,
        string $collection,
        string $filename,
        string $mimeType,
        string $binary,
        ?int $durationSeconds = null,
        ?string $bodyText = null,
    ): Media {
        $path = "lessons/{$lesson->id}/{$collection}/{$filename}";
        Storage::disk('public')->put($path, $binary);

        return Media::query()->create([
            'mediable_type' => $lesson->getMorphClass(),
            'mediable_id' => $lesson->id,
            'collection_name' => $collection,
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'file_name' => $filename,
            'mime_type' => $mimeType,
            'disk' => 'public',
            'path' => $path,
            'size' => strlen($binary),
            'duration_seconds' => $durationSeconds,
            'order_column' => 0,
            'custom_properties' => $bodyText !== null && $bodyText !== ''
                ? [
                    'body_text' => $bodyText,
                    'body_capture' => 'ready',
                    'body_captured_at' => now()->toIso8601String(),
                ]
                : null,
        ]);
    }
}
