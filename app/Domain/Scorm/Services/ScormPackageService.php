<?php

namespace App\Domain\Scorm\Services;

use App\Domain\Scorm\DTOs\ScormUploadData;
use App\Domain\Scorm\Events\ScormPackageUploaded;
use App\Domain\Scorm\Exceptions\InvalidScormPackageException;
use App\Models\ScormPackage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ScormPackageService
{
    public function __construct(
        protected ManifestParser $manifestParser,
    ) {}

    /**
     * Upload, extract, and register a SCORM package.
     */
    public function upload(ScormUploadData $data): ScormPackage
    {
        $file = $data->file;

        // Validate it's a proper ZIP
        $zip = new ZipArchive;
        $zipPath = $file->getPathname();

        if ($zip->open($zipPath) !== true) {
            throw InvalidScormPackageException::invalidZip();
        }

        // Check for imsmanifest.xml
        $manifestIndex = $zip->locateName('imsmanifest.xml');

        if ($manifestIndex === false) {
            $zip->close();

            throw InvalidScormPackageException::missingManifest();
        }

        // Read and parse manifest
        $manifestXml = $zip->getFromIndex($manifestIndex);
        $zip->close();

        if ($manifestXml === false || empty($manifestXml)) {
            throw InvalidScormPackageException::emptyPackage();
        }

        $manifestData = $this->manifestParser->parse($manifestXml);

        return DB::transaction(function () use ($data, $manifestData, $file) {
            // Create the package record first to get an ID for the path
            $package = ScormPackage::create([
                'title' => $manifestData->title,
                'version' => $manifestData->version,
                'identifier' => $manifestData->identifier,
                'original_filename' => $file->getClientOriginalName(),
                'disk' => 'local',
                'extraction_path' => '', // Will be updated after extraction
                'entry_point' => $manifestData->entryPoint,
                'metadata' => $manifestData->metadata,
                'file_size_bytes' => $file->getSize(),
                'uploaded_by' => $data->uploadedBy,
            ]);

            // Extract to storage
            $extractionPath = "scorm_packages/{$package->id}";
            $this->extractZip($file->getPathname(), $extractionPath);

            $package->update(['extraction_path' => $extractionPath]);

            ScormPackageUploaded::dispatch($package, $data->uploadedBy);

            return $package;
        });
    }

    /**
     * Delete a SCORM package and its extracted files.
     */
    public function delete(ScormPackage $package): void
    {
        DB::transaction(function () use ($package) {
            // Delete extracted files from storage
            if ($package->extraction_path) {
                Storage::disk($package->disk)->deleteDirectory($package->extraction_path);
            }

            $package->delete();
        });
    }

    /**
     * Extract a ZIP file to the given storage path.
     */
    protected function extractZip(string $zipPath, string $storagePath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw InvalidScormPackageException::invalidZip();
        }

        $disk = Storage::disk('local');
        $fullPath = $disk->path($storagePath);

        // Ensure the directory exists
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $zip->extractTo($fullPath);
        $zip->close();
    }
}
