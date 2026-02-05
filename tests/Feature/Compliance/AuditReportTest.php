<?php

use App\Domain\Compliance\DTOs\AuditReportFilter;
use App\Domain\Compliance\Services\AuditReportService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'lms_admin']);
    $this->learner = User::factory()->create(['role' => 'learner']);
    $this->reportService = app(AuditReportService::class);

    // Seed some audit log entries
    $events = [
        ['event_name' => 'UserEnrolled', 'aggregate_type' => 'Enrollment', 'aggregate_id' => 1],
        ['event_name' => 'UserEnrolled', 'aggregate_type' => 'Enrollment', 'aggregate_id' => 2],
        ['event_name' => 'EnrollmentCompleted', 'aggregate_type' => 'Enrollment', 'aggregate_id' => 1],
        ['event_name' => 'AssessmentAttemptStarted', 'aggregate_type' => 'AssessmentAttempt', 'aggregate_id' => 1],
        ['event_name' => 'AssessmentAttemptSubmitted', 'aggregate_type' => 'AssessmentAttempt', 'aggregate_id' => 1],
        ['event_name' => 'AssessmentGraded', 'aggregate_type' => 'AssessmentAttempt', 'aggregate_id' => 1],
        ['event_name' => 'CoursePublished', 'aggregate_type' => 'Course', 'aggregate_id' => 1],
    ];

    foreach ($events as $event) {
        DB::table('domain_event_log')->insert([
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_name' => $event['event_name'],
            'aggregate_type' => $event['aggregate_type'],
            'aggregate_id' => $event['aggregate_id'],
            'actor_id' => $this->learner->id,
            'metadata' => json_encode(['course_id' => 1]),
            'occurred_at' => now()->subDays(rand(1, 10)),
            'created_at' => now(),
        ]);
    }
});

describe('AuditReportService', function () {
    it('retrieves audit log entries within date range', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $logs = $this->reportService->getAuditLog($filter);

        expect($logs)->toHaveCount(7);
        expect($logs->first())->toHaveProperties([
            'event_name', 'aggregate_type', 'aggregate_id', 'occurred_at',
        ]);
    });

    it('filters by event types', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
            'event_types' => ['UserEnrolled'],
        ]);

        $logs = $this->reportService->getAuditLog($filter);

        expect($logs)->toHaveCount(2);
        expect($logs->every(fn ($log) => $log->event_name === 'UserEnrolled'))->toBeTrue();
    });

    it('generates summary statistics', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $summary = $this->reportService->getSummary($filter);

        expect($summary)->toHaveKeys([
            'total_events', 'events_by_type', 'events_by_category', 'unique_users', 'date_range',
        ]);
        expect($summary['total_events'])->toBe(7);
        expect($summary['events_by_type']['UserEnrolled'])->toBe(2);
        expect($summary['events_by_category']['enrollment'])->toBe(3); // 2 enrolled + 1 completed
        expect($summary['events_by_category']['assessment'])->toBe(3);
    });

    it('generates enrollment activity report', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $activity = $this->reportService->getEnrollmentActivityReport($filter);

        expect($activity)->not->toBeEmpty();
        expect($activity->first())->toHaveProperties([
            'date', 'enrollments', 'completions', 'drops', 'reenrollments',
        ]);
    });

    it('generates assessment activity report', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $activity = $this->reportService->getAssessmentActivityReport($filter);

        expect($activity)->not->toBeEmpty();
        expect($activity->first())->toHaveProperties([
            'date', 'attempts_started', 'attempts_submitted', 'graded',
        ]);
    });

    it('exports to CSV format', function () {
        $filter = AuditReportFilter::fromArray([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $csv = $this->reportService->exportToCsv($filter);

        expect($csv)->toContain('ID,Event ID,Event Name');
        expect($csv)->toContain('UserEnrolled');
        expect($csv)->toContain('AssessmentGraded');
    });
});

describe('ComplianceReportController', function () {
    it('allows lms_admin to access audit report dashboard', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('compliance.audit-reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('compliance/AuditReportIndex', shouldExist: false)
            ->has('summary')
            ->has('enrollmentActivity')
            ->has('assessmentActivity')
            ->has('eventCategories')
        );
    });

    it('denies learner access to audit reports', function () {
        $response = $this->actingAs($this->learner)
            ->get(route('compliance.audit-reports.index'));

        $response->assertForbidden();
    });

    it('returns audit log via API endpoint', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('compliance.audit-reports.logs', [
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['total', 'filter'],
        ]);
    });

    it('exports CSV with proper headers', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('compliance.audit-reports.export-csv', [
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    });

    it('validates date range in requests', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('compliance.audit-reports.logs', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDays(10)->toDateString(), // end before start
            ]));

        $response->assertSessionHasErrors(['start_date']);
    });
});
