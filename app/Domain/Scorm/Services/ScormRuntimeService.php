<?php

namespace App\Domain\Scorm\Services;

use App\Domain\Progress\Services\ProgressTrackingService;
use App\Domain\Scorm\Events\ScormLessonCompleted;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormScoTracking;

class ScormRuntimeService
{
    public function __construct(
        protected ProgressTrackingService $progressService,
    ) {}

    /**
     * Initialize a SCORM session (LMSInitialize / Initialize).
     */
    public function initialize(Enrollment $enrollment, Lesson $lesson): ScormScoTracking
    {
        $package = $lesson->scormPackage;

        $tracking = ScormScoTracking::firstOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'scorm_package_id' => $package->id,
                'lesson_status' => 'not attempted',
                'completion_status' => 'not attempted',
                'success_status' => 'unknown',
                'launched_at' => now(),
            ]
        );

        // Determine entry mode
        $entry = ($tracking->exit_type === 'suspend') ? 'resume' : 'ab-initio';

        $tracking->update([
            'entry' => $entry,
            'last_accessed_at' => now(),
        ]);

        return $tracking;
    }

    /**
     * Get a CMI data value (LMSGetValue / GetValue).
     */
    public function getValue(ScormScoTracking $tracking, string $element): string
    {
        return match ($element) {
            // SCORM 1.2 core
            'cmi.core.lesson_status' => $tracking->lesson_status ?? 'not attempted',
            'cmi.core.score.raw' => (string) ($tracking->score_raw ?? ''),
            'cmi.core.score.min' => (string) ($tracking->score_min ?? ''),
            'cmi.core.score.max' => (string) ($tracking->score_max ?? ''),
            'cmi.core.lesson_location' => $tracking->location ?? '',
            'cmi.core.total_time' => $tracking->total_time ?? '0000:00:00.00',
            'cmi.core.entry' => $tracking->entry ?? '',
            'cmi.core.exit' => $tracking->exit_type ?? '',
            'cmi.suspend_data' => $tracking->suspend_data ?? '',
            'cmi.core.session_time' => $tracking->session_time ?? '0000:00:00.00',

            // SCORM 2004 core
            'cmi.completion_status' => $tracking->completion_status ?? 'not attempted',
            'cmi.success_status' => $tracking->success_status ?? 'unknown',
            'cmi.score.raw' => (string) ($tracking->score_raw ?? ''),
            'cmi.score.min' => (string) ($tracking->score_min ?? ''),
            'cmi.score.max' => (string) ($tracking->score_max ?? ''),
            'cmi.score.scaled' => (string) ($tracking->score_scaled ?? ''),
            'cmi.location' => $tracking->location ?? '',
            'cmi.total_time' => $tracking->total_time ?? 'PT0S',
            'cmi.entry' => $tracking->entry ?? '',
            'cmi.exit' => $tracking->exit_type ?? '',
            'cmi.session_time' => $tracking->session_time ?? 'PT0S',

            // Any other element: check cmi_data JSON catchall
            default => $this->getFromCmiData($tracking, $element),
        };
    }

    /**
     * Set a CMI data value (LMSSetValue / SetValue).
     */
    public function setValue(ScormScoTracking $tracking, string $element, string $value): bool
    {
        $updated = match ($element) {
            // SCORM 1.2 core
            'cmi.core.lesson_status' => $this->updateField($tracking, 'lesson_status', $value),
            'cmi.core.score.raw' => $this->updateField($tracking, 'score_raw', $value),
            'cmi.core.score.min' => $this->updateField($tracking, 'score_min', $value),
            'cmi.core.score.max' => $this->updateField($tracking, 'score_max', $value),
            'cmi.core.lesson_location' => $this->updateField($tracking, 'location', $value),
            'cmi.core.exit' => $this->updateField($tracking, 'exit_type', $value),
            'cmi.core.session_time' => $this->updateField($tracking, 'session_time', $value),
            'cmi.suspend_data' => $this->updateField($tracking, 'suspend_data', $value),

            // SCORM 2004 core
            'cmi.completion_status' => $this->updateField($tracking, 'completion_status', $value),
            'cmi.success_status' => $this->updateField($tracking, 'success_status', $value),
            'cmi.score.raw' => $this->updateField($tracking, 'score_raw', $value),
            'cmi.score.min' => $this->updateField($tracking, 'score_min', $value),
            'cmi.score.max' => $this->updateField($tracking, 'score_max', $value),
            'cmi.score.scaled' => $this->updateField($tracking, 'score_scaled', $value),
            'cmi.location' => $this->updateField($tracking, 'location', $value),
            'cmi.exit' => $this->updateField($tracking, 'exit_type', $value),
            'cmi.session_time' => $this->updateField($tracking, 'session_time', $value),

            // Any other element: store in cmi_data JSON catchall
            default => $this->setInCmiData($tracking, $element, $value),
        };

        return $updated;
    }

    /**
     * Commit CMI data to persistent storage (LMSCommit / Commit).
     */
    public function commit(ScormScoTracking $tracking): bool
    {
        $tracking->update(['last_accessed_at' => now()]);

        return true;
    }

    /**
     * Finish the SCORM session (LMSFinish / Terminate).
     *
     * Accumulates session_time into total_time and triggers completion if appropriate.
     */
    public function finish(ScormScoTracking $tracking): bool
    {
        // Accumulate session time into total time
        if ($tracking->session_time) {
            $tracking->total_time = $this->addTimes(
                $tracking->total_time ?? 'PT0S',
                $tracking->session_time
            );
        }

        $tracking->finished_at = now();
        $tracking->last_accessed_at = now();
        $tracking->save();

        // Check if SCORM reports completion
        if ($tracking->is_completed || $tracking->is_passed) {
            $enrollment = $tracking->enrollment;
            $lesson = $tracking->lesson;

            // Integrate with existing progress system
            $this->progressService->completeLesson($enrollment, $lesson);

            ScormLessonCompleted::dispatch($enrollment, $lesson, $tracking, $enrollment->user_id);
        }

        return true;
    }

    protected function updateField(ScormScoTracking $tracking, string $field, string $value): bool
    {
        $tracking->{$field} = $value;

        return true;
    }

    protected function getFromCmiData(ScormScoTracking $tracking, string $element): string
    {
        $cmiData = $tracking->cmi_data ?? [];

        return (string) ($cmiData[$element] ?? '');
    }

    protected function setInCmiData(ScormScoTracking $tracking, string $element, string $value): bool
    {
        $cmiData = $tracking->cmi_data ?? [];
        $cmiData[$element] = $value;
        $tracking->cmi_data = $cmiData;

        return true;
    }

    /**
     * Add two time durations together.
     * Handles both SCORM 1.2 format (HHHH:MM:SS.SS) and ISO 8601 duration (PTnHnMnS).
     */
    protected function addTimes(string $totalTime, string $sessionTime): string
    {
        $totalSeconds = $this->timeToSeconds($totalTime);
        $sessionSeconds = $this->timeToSeconds($sessionTime);

        $sum = $totalSeconds + $sessionSeconds;

        // Return in ISO 8601 format
        $hours = (int) floor($sum / 3600);
        $minutes = (int) floor(($sum % 3600) / 60);
        $seconds = (int) ($sum % 60);

        $parts = ['PT'];
        if ($hours > 0) {
            $parts[] = "{$hours}H";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}M";
        }
        $parts[] = "{$seconds}S";

        return implode('', $parts);
    }

    /**
     * Convert a time string to seconds.
     */
    protected function timeToSeconds(string $time): int
    {
        // ISO 8601 duration: PT1H30M45S
        if (str_starts_with($time, 'PT') || str_starts_with($time, 'P')) {
            preg_match('/(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $time, $matches);

            $hours = (int) ($matches[1] ?? 0);
            $minutes = (int) ($matches[2] ?? 0);
            $seconds = (int) ($matches[3] ?? 0);

            return ($hours * 3600) + ($minutes * 60) + $seconds;
        }

        // SCORM 1.2 format: HHHH:MM:SS.SS
        $parts = explode(':', $time);

        if (count($parts) === 3) {
            return (int) ((int) $parts[0] * 3600 + (int) $parts[1] * 60 + (float) $parts[2]);
        }

        return 0;
    }
}
