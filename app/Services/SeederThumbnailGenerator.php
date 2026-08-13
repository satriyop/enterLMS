<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates local branded placeholder thumbnails for seeders.
 * Avoids external CDN/Unsplash dependency and deleted fixture assets.
 */
class SeederThumbnailGenerator
{
    /**
     * Create a gradient JPEG thumbnail with a title overlay.
     *
     * @return string|null Relative path on the public disk (e.g. courses/thumbnails/foo.jpg)
     */
    public function generate(string $title, string $directory = 'courses/thumbnails', ?string $filename = null): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $filename ??= Str::slug(Str::limit($title, 40, '')).'-'.Str::lower(Str::random(6)).'.jpg';
        $path = trim($directory, '/').'/'.$filename;

        $width = 800;
        $height = 450;

        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return null;
        }

        [$r1, $g1, $b1, $r2, $g2, $b2] = $this->paletteFor($title);

        // Vertical gradient
        for ($y = 0; $y < $height; $y++) {
            $t = $y / max(1, $height - 1);
            $r = (int) round($r1 + ($r2 - $r1) * $t);
            $g = (int) round($g1 + ($g2 - $g1) * $t);
            $b = (int) round($b1 + ($b2 - $b1) * $t);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Soft overlay panel
        $panel = imagecolorallocatealpha($image, 15, 23, 42, 70);
        imagefilledrectangle($image, 40, $height - 160, $width - 40, $height - 40, $panel);

        $white = imagecolorallocate($image, 255, 255, 255);
        $muted = imagecolorallocate($image, 226, 232, 240);

        $label = 'EnterLMS';
        imagestring($image, 3, 60, $height - 140, $label, $muted);

        $lines = $this->wrapTitle($title, 42);
        $lineY = $height - 115;
        foreach ($lines as $line) {
            imagestring($image, 5, 60, $lineY, $line, $white);
            $lineY += 22;
        }

        ob_start();
        imagejpeg($image, null, 85);
        $binary = ob_get_clean();
        imagedestroy($image);

        if ($binary === false || $binary === '') {
            return null;
        }

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * @return array{0:int,1:int,2:int,3:int,4:int,5:int}
     */
    private function paletteFor(string $title): array
    {
        $palettes = [
            [15, 23, 42, 30, 64, 175],   // slate → blue
            [20, 83, 45, 5, 150, 105],   // green
            [67, 20, 7, 194, 65, 12],    // amber
            [49, 46, 129, 124, 58, 237], // indigo → violet
            [12, 74, 110, 8, 145, 178],  // cyan
            [76, 29, 149, 190, 24, 93],  // purple → rose
        ];

        $index = abs(crc32($title)) % count($palettes);

        return $palettes[$index];
    }

    /**
     * @return list<string>
     */
    private function wrapTitle(string $title, int $maxLen): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);
        if (strlen($title) <= $maxLen) {
            return [$title];
        }

        $words = explode(' ', $title);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if (strlen($candidate) > $maxLen) {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, 3);
    }
}
