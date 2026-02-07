<?php

namespace App\Http\Controllers;

use App\Domain\Scorm\DTOs\ScormUploadData;
use App\Domain\Scorm\Exceptions\InvalidScormPackageException;
use App\Domain\Scorm\Exceptions\ManifestParsingException;
use App\Domain\Scorm\Services\ScormPackageService;
use App\Http\Requests\Scorm\StoreScormPackageRequest;
use App\Http\Resources\Scorm\ScormPackageResource;
use App\Models\ScormPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ScormPackageController extends Controller
{
    public function __construct(
        protected ScormPackageService $packageService,
    ) {}

    /**
     * List all SCORM packages.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', ScormPackage::class);

        $packages = ScormPackage::query()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => ScormPackageResource::collection($packages),
        ]);
    }

    /**
     * Upload a new SCORM package.
     */
    public function store(StoreScormPackageRequest $request): JsonResponse
    {
        try {
            $package = $this->packageService->upload(
                new ScormUploadData(
                    file: $request->file('file'),
                    uploadedBy: $request->user()->id,
                )
            );

            return response()->json([
                'data' => new ScormPackageResource($package),
                'message' => 'Paket SCORM berhasil diunggah.',
            ], 201);
        } catch (InvalidScormPackageException|ManifestParsingException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a SCORM package.
     */
    public function destroy(ScormPackage $scormPackage): JsonResponse
    {
        Gate::authorize('delete', $scormPackage);

        $this->packageService->delete($scormPackage);

        return response()->json([
            'message' => 'Paket SCORM berhasil dihapus.',
        ]);
    }
}
