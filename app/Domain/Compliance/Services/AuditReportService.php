<?php

namespace App\Domain\Compliance\Services;

use App\Domain\Compliance\DTOs\AuditReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Generates compliance audit reports from domain event logs.
 *
 * OJK-relevant event categories:
 * - Enrollment: UserEnrolled, EnrollmentCompleted, UserDropped, UserReenrolled, CourseStarted
 * - Assessment: AssessmentAttemptStarted, AssessmentAttemptSubmitted, AssessmentGraded
 * - Progress: LessonCompleted, ProgressUpdated
 * - Learning Path: PathEnrollmentCreated, PathCompleted, PathDropped
 * - Course Admin: CoursePublished, CourseUnpublished, CourseArchived
 */
class AuditReportService
{
    /**
     * OJK compliance-relevant event types grouped by category.
     *
     * @var array<string, array<string>>
     */
    public const EVENT_CATEGORIES = [
        'enrollment' => [
            'UserEnrolled',
            'EnrollmentCompleted',
            'UserDropped',
            'UserReenrolled',
            'CourseStarted',
        ],
        'assessment' => [
            'AssessmentAttemptStarted',
            'AssessmentAttemptSubmitted',
            'AssessmentGraded',
        ],
        'progress' => [
            'LessonCompleted',
            'ProgressUpdated',
        ],
        'learning_path' => [
            'PathEnrollmentCreated',
            'PathCompleted',
            'PathDropped',
            'CourseUnlockedInPath',
        ],
        'course_admin' => [
            'CoursePublished',
            'CourseUnpublished',
            'CourseArchived',
        ],
    ];

    /**
     * Get raw audit log entries matching the filter.
     *
     * @return Collection<int, mixed>
     */
    public function getAuditLog(AuditReportFilter $filter): Collection
    {
        $query = DB::table('domain_event_log')
            ->leftJoin('users', 'domain_event_log.actor_id', '=', 'users.id')
            ->select([
                'domain_event_log.id',
                'domain_event_log.event_id',
                'domain_event_log.event_name',
                'domain_event_log.aggregate_type',
                'domain_event_log.aggregate_id',
                'domain_event_log.actor_id',
                'users.name as actor_name',
                'domain_event_log.metadata',
                'domain_event_log.occurred_at',
            ])
            ->whereBetween('domain_event_log.occurred_at', [
                $filter->startDate->toDateTimeString(),
                $filter->endDate->toDateTimeString(),
            ])
            ->orderBy('domain_event_log.occurred_at', 'desc');

        if ($filter->eventTypes !== null) {
            $query->whereIn('domain_event_log.event_name', $filter->eventTypes);
        }

        if ($filter->aggregateTypes !== null) {
            $query->whereIn('domain_event_log.aggregate_type', $filter->aggregateTypes);
        }

        if ($filter->userId !== null) {
            $query->where('domain_event_log.actor_id', $filter->userId);
        }

        if ($filter->courseId !== null) {
            // Course ID can be in aggregate_id (for Course events) or in metadata
            $query->where(function ($q) use ($filter) {
                $q->where(function ($q2) use ($filter) {
                    $q2->where('domain_event_log.aggregate_type', 'Course')
                        ->where('domain_event_log.aggregate_id', $filter->courseId);
                })->orWhereRaw(
                    "JSON_EXTRACT(domain_event_log.metadata, '$.course_id') = ?",
                    [$filter->courseId]
                );
            });
        }

        return $query->get();
    }

    /**
     * Get summary statistics for compliance reporting.
     *
     * @return array{
     *     total_events: int,
     *     events_by_type: array<string, int>,
     *     events_by_category: array<string, int>,
     *     unique_users: int,
     *     date_range: array{start: string, end: string}
     * }
     */
    public function getSummary(AuditReportFilter $filter): array
    {
        $logs = $this->getAuditLog($filter);

        // Count by event type
        $eventsByType = $logs->groupBy('event_name')
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Count by category
        $eventsByCategory = [];
        foreach (self::EVENT_CATEGORIES as $category => $eventTypes) {
            $count = $logs->filter(
                fn ($log) => in_array($log->event_name, $eventTypes, true)
            )->count();
            if ($count > 0) {
                $eventsByCategory[$category] = $count;
            }
        }

        // Unique actors
        $uniqueUsers = $logs->pluck('actor_id')
            ->filter()
            ->unique()
            ->count();

        return [
            'total_events' => $logs->count(),
            'events_by_type' => $eventsByType,
            'events_by_category' => $eventsByCategory,
            'unique_users' => $uniqueUsers,
            'date_range' => [
                'start' => $filter->startDate->toDateString(),
                'end' => $filter->endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get enrollment activity report.
     * Tracks: enrollments, completions, drops for compliance.
     *
     * @return Collection<int, mixed>
     */
    public function getEnrollmentActivityReport(AuditReportFilter $filter): Collection
    {
        $enrollmentEvents = [
            'UserEnrolled',
            'EnrollmentCompleted',
            'UserDropped',
            'UserReenrolled',
        ];

        $filterWithEvents = new AuditReportFilter(
            startDate: $filter->startDate,
            endDate: $filter->endDate,
            eventTypes: $enrollmentEvents,
            aggregateTypes: $filter->aggregateTypes,
            userId: $filter->userId,
            courseId: $filter->courseId,
        );

        $logs = $this->getAuditLog($filterWithEvents);

        // Group by date and count each event type
        return $logs->groupBy(function ($log) {
            return substr($log->occurred_at, 0, 10); // Extract date portion
        })->map(function ($dayLogs, $date) {
            return (object) [
                'date' => $date,
                'enrollments' => $dayLogs->where('event_name', 'UserEnrolled')->count(),
                'completions' => $dayLogs->where('event_name', 'EnrollmentCompleted')->count(),
                'drops' => $dayLogs->where('event_name', 'UserDropped')->count(),
                'reenrollments' => $dayLogs->where('event_name', 'UserReenrolled')->count(),
            ];
        })->sortByDesc('date')->values();
    }

    /**
     * Get assessment activity report for compliance.
     * Tracks: attempts started, submitted, graded.
     *
     * @return Collection<int, mixed>
     */
    public function getAssessmentActivityReport(AuditReportFilter $filter): Collection
    {
        $assessmentEvents = [
            'AssessmentAttemptStarted',
            'AssessmentAttemptSubmitted',
            'AssessmentGraded',
        ];

        $filterWithEvents = new AuditReportFilter(
            startDate: $filter->startDate,
            endDate: $filter->endDate,
            eventTypes: $assessmentEvents,
            aggregateTypes: $filter->aggregateTypes,
            userId: $filter->userId,
            courseId: $filter->courseId,
        );

        $logs = $this->getAuditLog($filterWithEvents);

        return $logs->groupBy(function ($log) {
            return substr($log->occurred_at, 0, 10);
        })->map(function ($dayLogs, $date) {
            return (object) [
                'date' => $date,
                'attempts_started' => $dayLogs->where('event_name', 'AssessmentAttemptStarted')->count(),
                'attempts_submitted' => $dayLogs->where('event_name', 'AssessmentAttemptSubmitted')->count(),
                'graded' => $dayLogs->where('event_name', 'AssessmentGraded')->count(),
            ];
        })->sortByDesc('date')->values();
    }

    /**
     * Get user activity audit for a specific user.
     * Used for individual compliance audits.
     *
     * @return Collection<int, mixed>
     */
    public function getUserActivityAudit(int $userId, AuditReportFilter $filter): Collection
    {
        $filterWithUser = new AuditReportFilter(
            startDate: $filter->startDate,
            endDate: $filter->endDate,
            eventTypes: $filter->eventTypes,
            aggregateTypes: $filter->aggregateTypes,
            userId: $userId,
            courseId: $filter->courseId,
        );

        return $this->getAuditLog($filterWithUser);
    }

    /**
     * Export audit log to CSV format.
     *
     * @return string CSV content
     */
    public function exportToCsv(AuditReportFilter $filter): string
    {
        $logs = $this->getAuditLog($filter);

        $headers = [
            'ID',
            'Event ID',
            'Event Name',
            'Aggregate Type',
            'Aggregate ID',
            'Actor ID',
            'Actor Name',
            'Occurred At',
            'Metadata',
        ];

        $rows = $logs->map(fn ($log) => [
            $log->id,
            $log->event_id,
            $log->event_name,
            $log->aggregate_type,
            $log->aggregate_id,
            $log->actor_id ?? '',
            $log->actor_name ?? '',
            $log->occurred_at,
            $log->metadata,
        ]);

        $csv = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', (string) $val).'"', $row))."\n";
        }

        return $csv;
    }

    /**
     * Get all available event categories for filtering.
     *
     * @return array<string, array<string>>
     */
    public function getEventCategories(): array
    {
        return self::EVENT_CATEGORIES;
    }
}
