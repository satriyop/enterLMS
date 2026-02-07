<?php

namespace App\Domain\Scorm\Exceptions;

use RuntimeException;

class ManifestParsingException extends RuntimeException
{
    public static function invalidXml(string $error): self
    {
        return new self("Gagal membaca manifest SCORM: {$error}");
    }

    public static function missingRequiredElement(string $element): self
    {
        return new self("Elemen wajib tidak ditemukan dalam manifest: {$element}");
    }

    public static function unsupportedVersion(string $version): self
    {
        return new self("Versi SCORM tidak didukung: {$version}");
    }
}
