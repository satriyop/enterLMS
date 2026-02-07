<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScormPlayerController extends Controller
{
    /**
     * Launch the SCORM player for a lesson.
     */
    public function launch(Request $request, Lesson $lesson): InertiaResponse
    {
        $enrollment = $this->verifyEnrollment($request, $lesson);

        if (! $enrollment) {
            abort(403, 'Anda tidak terdaftar di kursus ini.');
        }

        if (! $lesson->has_scorm || ! $lesson->scormPackage) {
            abort(422, 'Lesson ini bukan konten SCORM.');
        }

        $package = $lesson->scormPackage;

        return Inertia::render('scorm/Player', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
            ],
            'package' => [
                'id' => $package->id,
                'version' => $package->version,
                'entry_point' => $package->entry_point,
                'content_url' => route('scorm.player.content', [
                    'scormPackage' => $package->id,
                    'path' => $package->entry_point,
                ]),
            ],
            'runtime_urls' => [
                'initialize' => route('scorm.runtime.initialize', $lesson),
                'get_value' => route('scorm.runtime.getValue', $lesson),
                'set_value' => route('scorm.runtime.setValue', $lesson),
                'commit' => route('scorm.runtime.commit', $lesson),
                'finish' => route('scorm.runtime.finish', $lesson),
            ],
        ]);
    }

    /**
     * Serve SCORM package content files (HTML, JS, CSS, images, etc.).
     */
    public function serveContent(Request $request, ScormPackage $scormPackage, string $path): BinaryFileResponse|Response
    {
        // Verify user has enrollment access
        $hasAccess = Enrollment::where('user_id', $request->user()->id)
            ->whereHas('course.sections.lessons', function ($query) use ($scormPackage) {
                $query->where('scorm_package_id', $scormPackage->id);
            })
            ->exists();

        if (! $hasAccess && ! $request->user()->canManageCourses()) {
            abort(403, 'Anda tidak memiliki akses ke konten ini.');
        }

        $disk = Storage::disk($scormPackage->disk);
        $filePath = $scormPackage->extraction_path.'/'.$path;

        if (! $disk->exists($filePath)) {
            abort(404);
        }

        $fullPath = $disk->path($filePath);
        $mimeType = $this->getMimeType($path);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    protected function verifyEnrollment(Request $request, Lesson $lesson): ?Enrollment
    {
        $course = $lesson->section->course;

        return Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();
    }

    protected function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'html', 'htm' => 'text/html',
            'js' => 'application/javascript',
            'css' => 'text/css',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
            'swf' => 'application/x-shockwave-flash',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            default => 'application/octet-stream',
        };
    }
}
