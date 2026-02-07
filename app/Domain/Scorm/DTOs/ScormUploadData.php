<?php

namespace App\Domain\Scorm\DTOs;

use Illuminate\Http\UploadedFile;

/**
 * @phpstan-type ScormUploadDataArray array{
 *     file: UploadedFile,
 *     uploaded_by: int
 * }
 */
final readonly class ScormUploadData
{
    public function __construct(
        public UploadedFile $file,
        public int $uploadedBy,
    ) {}

    /**
     * @param  ScormUploadDataArray  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            uploadedBy: $data['uploaded_by'],
        );
    }
}
