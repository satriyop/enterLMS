<?php

use App\Http\Controllers\ComplianceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('compliance')->name('compliance.')->group(function () {
    // Audit Report Dashboard (Inertia page)
    Route::get('audit-reports', [ComplianceReportController::class, 'index'])
        ->name('audit-reports.index');

    // API endpoints for report data
    Route::get('audit-reports/logs', [ComplianceReportController::class, 'auditLog'])
        ->name('audit-reports.logs');

    Route::get('audit-reports/summary', [ComplianceReportController::class, 'summary'])
        ->name('audit-reports.summary');

    Route::get('audit-reports/enrollment-activity', [ComplianceReportController::class, 'enrollmentActivity'])
        ->name('audit-reports.enrollment-activity');

    Route::get('audit-reports/assessment-activity', [ComplianceReportController::class, 'assessmentActivity'])
        ->name('audit-reports.assessment-activity');

    Route::get('audit-reports/user/{userId}/activity', [ComplianceReportController::class, 'userActivity'])
        ->name('audit-reports.user-activity');

    // Export
    Route::get('audit-reports/export/csv', [ComplianceReportController::class, 'exportCsv'])
        ->name('audit-reports.export-csv');
});
