<?php

namespace App\Domain\Scorm\Exceptions;

use RuntimeException;

class InvalidScormPackageException extends RuntimeException
{
    public static function missingManifest(): self
    {
        return new self('Paket SCORM tidak valid: file imsmanifest.xml tidak ditemukan.');
    }

    public static function invalidZip(): self
    {
        return new self('File yang diunggah bukan file ZIP yang valid.');
    }

    public static function emptyPackage(): self
    {
        return new self('Paket SCORM kosong atau tidak memiliki konten.');
    }

    public static function noEntryPoint(): self
    {
        return new self('Paket SCORM tidak memiliki entry point (file utama) yang valid.');
    }
}
