<?php

namespace App\Http\Controllers;

use App\Domain\Compliance\DTOs\AuditReportFilter;
use App\Domain\Compliance\Services\AuditReportService;
use App\Http\Requests\Compliance\AuditReportRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ComplianceReportController extends Controller
{
    public function __construct(
        protected AuditReportService $reportService
    ) {}

    /**
     * Display the audit report dashboard.
     */
    public function index(Request $request): InertiaResponse
    {
        Gate::authorize('viewComplianceReports');

        // Default to last 30 days
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        $filter = AuditReportFilter::fromArray([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $summary = $this->reportService->getSummary($filter);
        $enrollmentActivity = $this->reportService->getEnrollmentActivityReport($filter);
        $assessmentActivity = $this->reportService->getAssessmentActivityReport($filter);

        return Inertia::render('compliance/AuditReportIndex', [
            'summary' => $summary,
            'enrollmentActivity' => $enrollmentActivity,
            'assessmentActivity' => $assessmentActivity,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'eventCategories' => $this->reportService->getEventCategories(),
        ]);
    }

    /**
     * Get audit log entries (API endpoint for filtering).
     */
    public function auditLog(AuditReportRequest $request): JsonResponse
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $logs = $this->reportService->getAuditLog($filter);

        return response()->json([
            'data' => $logs,
            'meta' => [
                'total' => $logs->count(),
                'filter' => [
                    'start_date' => $filter->startDate->toDateString(),
                    'end_date' => $filter->endDate->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Get summary statistics (API endpoint).
     */
    public function summary(AuditReportRequest $request): JsonResponse
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $summary = $this->reportService->getSummary($filter);

        return response()->json(['data' => $summary]);
    }

    /**
     * Get enrollment activity report (API endpoint).
     */
    public function enrollmentActivity(AuditReportRequest $request): JsonResponse
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $activity = $this->reportService->getEnrollmentActivityReport($filter);

        return response()->json(['data' => $activity]);
    }

    /**
     * Get assessment activity report (API endpoint).
     */
    public function assessmentActivity(AuditReportRequest $request): JsonResponse
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $activity = $this->reportService->getAssessmentActivityReport($filter);

        return response()->json(['data' => $activity]);
    }

    /**
     * Get user activity audit (API endpoint).
     */
    public function userActivity(AuditReportRequest $request, int $userId): JsonResponse
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $activity = $this->reportService->getUserActivityAudit($userId, $filter);

        return response()->json([
            'data' => $activity,
            'meta' => [
                'user_id' => $userId,
                'total' => $activity->count(),
            ],
        ]);
    }

    /**
     * Export audit log to CSV.
     */
    public function exportCsv(AuditReportRequest $request): Response
    {
        $filter = AuditReportFilter::fromArray($request->validated());
        $csv = $this->reportService->exportToCsv($filter);

        $filename = sprintf(
            'audit-report-%s-to-%s.csv',
            $filter->startDate->format('Y-m-d'),
            $filter->endDate->format('Y-m-d')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
