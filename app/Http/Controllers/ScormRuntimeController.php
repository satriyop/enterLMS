<?php

namespace App\Http\Controllers;

use App\Domain\Scorm\Services\ScormRuntimeService;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormScoTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScormRuntimeController extends Controller
{
    public function __construct(
        protected ScormRuntimeService $runtimeService,
    ) {}

    /**
     * Initialize a SCORM session.
     */
    public function initialize(Request $request, Lesson $lesson): JsonResponse
    {
        $enrollment = $this->getActiveEnrollment($request, $lesson);

        if (! $enrollment) {
            return response()->json(['error' => 'Anda tidak terdaftar di kursus ini.'], 403);
        }

        if (! $lesson->has_scorm || ! $lesson->scormPackage) {
            return response()->json(['error' => 'Lesson ini bukan konten SCORM.'], 422);
        }

        $tracking = $this->runtimeService->initialize($enrollment, $lesson);

        return response()->json([
            'success' => true,
            'version' => $lesson->scormPackage->version,
            'tracking_id' => $tracking->id,
        ]);
    }

    /**
     * Get a CMI value.
     */
    public function getValue(Request $request, Lesson $lesson): JsonResponse
    {
        $request->validate(['element' => 'required|string']);

        $tracking = $this->getTracking($request, $lesson);

        if (! $tracking) {
            return response()->json(['error' => 'Sesi SCORM tidak ditemukan.'], 404);
        }

        $value = $this->runtimeService->getValue($tracking, $request->input('element'));

        return response()->json([
            'value' => $value,
            'error_code' => '0',
        ]);
    }

    /**
     * Set a CMI value.
     */
    public function setValue(Request $request, Lesson $lesson): JsonResponse
    {
        $request->validate([
            'element' => 'required|string',
            'value' => 'present|string',
        ]);

        $tracking = $this->getTracking($request, $lesson);

        if (! $tracking) {
            return response()->json(['error' => 'Sesi SCORM tidak ditemukan.'], 404);
        }

        $this->runtimeService->setValue(
            $tracking,
            $request->input('element'),
            $request->input('value')
        );

        // Persist immediately since each API call is stateless
        $tracking->save();

        return response()->json([
            'success' => true,
            'error_code' => '0',
        ]);
    }

    /**
     * Commit data to persistent storage.
     */
    public function commit(Request $request, Lesson $lesson): JsonResponse
    {
        $tracking = $this->getTracking($request, $lesson);

        if (! $tracking) {
            return response()->json(['error' => 'Sesi SCORM tidak ditemukan.'], 404);
        }

        // Save any pending changes before commit
        $tracking->save();
        $this->runtimeService->commit($tracking);

        return response()->json([
            'success' => true,
            'error_code' => '0',
        ]);
    }

    /**
     * Finish/terminate the SCORM session.
     */
    public function finish(Request $request, Lesson $lesson): JsonResponse
    {
        $tracking = $this->getTracking($request, $lesson);

        if (! $tracking) {
            return response()->json(['error' => 'Sesi SCORM tidak ditemukan.'], 404);
        }

        // Save any pending changes before finish
        $tracking->save();
        $this->runtimeService->finish($tracking);

        return response()->json([
            'success' => true,
            'error_code' => '0',
        ]);
    }

    protected function getActiveEnrollment(Request $request, Lesson $lesson): ?Enrollment
    {
        $course = $lesson->section->course;

        return Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();
    }

    protected function getTracking(Request $request, Lesson $lesson): ?ScormScoTracking
    {
        $enrollment = $this->getActiveEnrollment($request, $lesson);

        if (! $enrollment) {
            return null;
        }

        return ScormScoTracking::where('enrollment_id', $enrollment->id)
            ->where('lesson_id', $lesson->id)
            ->first();
    }
}
