<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Progress Calculator
    |--------------------------------------------------------------------------
    |
    | The default progress calculator strategy to use for enrollment progress.
    |
    | Options: 'lesson_based', 'weighted', 'assessment_inclusive'
    |
    */

    'progress_calculator' => env('LMS_PROGRESS_CALCULATOR', 'lesson_based'),

    /*
    |--------------------------------------------------------------------------
    | Lesson Completion Thresholds
    |--------------------------------------------------------------------------
    |
    | Auto-completion thresholds for different content types.
    | Values are percentages (0-100).
    |
    */

    'completion_thresholds' => [
        // Media content (video, audio) completes at this percentage watched
        'media' => (int) env('LMS_MEDIA_COMPLETION_THRESHOLD', 90),

        // Page-based content completes when this percentage of pages viewed
        // 100 = must view all pages, 90 = can skip last 10%
        'pages' => (int) env('LMS_PAGES_COMPLETION_THRESHOLD', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Grading Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for auto-grading behavior.
    |
    */

    'grading' => [
        // Partial credit settings for short answer fuzzy matching
        'short_answer_similarity_threshold' => 0.8,

        // Whether to enable partial credit for multiple choice
        'multiple_choice_partial_credit' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Default notification channels for different event types.
    |
    */

    'notifications' => [
        'enrollment_created' => ['mail', 'database'],
        'enrollment_completed' => ['mail', 'database'],
        'assessment_graded' => ['mail', 'database'],
        'default' => ['database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enrollment Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for course enrollments.
    |
    */

    'enrollment' => [
        // Whether to automatically complete enrollment when all lessons are done
        'auto_complete' => true,

        // Whether to allow re-enrollment after dropping
        'allow_re_enrollment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assessment Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for assessments.
    |
    */

    'assessment' => [
        // Default passing score (percentage)
        'default_passing_score' => 70,

        // Default maximum attempts (0 = unlimited)
        'default_max_attempts' => 3,

        // Default time limit in minutes (null = no limit)
        'default_time_limit' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | LMS Mode
    |--------------------------------------------------------------------------
    |
    | The LMS can operate in two modes:
    | - 'internal': All courses are free, no payments
    | - 'commercial': Courses can be paid, payment system enabled
    |
    */

    'mode' => env('LMS_MODE', 'internal'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported by the LMS for course pricing.
    | Uses ISO 4217 currency codes.
    |
    */

    'supported_currencies' => [
        'IDR' => 'Indonesian Rupiah',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'SGD' => 'Singapore Dollar',
    ],

    'default_currency' => env('LMS_DEFAULT_CURRENCY', 'IDR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment processing in commercial mode.
    |
    */

    'payment' => [
        'enabled' => env('LMS_PAYMENT_ENABLED', false),
        'gateway' => env('LMS_PAYMENT_GATEWAY', 'midtrans'), // midtrans, stripe, etc.
        'sandbox' => env('LMS_PAYMENT_SANDBOX', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | SCORM Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SCORM package management and runtime.
    |
    */

    'scorm' => [
        // Maximum upload size in kilobytes (default: 250MB)
        'max_upload_size_kb' => (int) env('LMS_SCORM_MAX_UPLOAD_KB', 256000),

        // Storage disk for extracted SCORM packages
        'disk' => env('LMS_SCORM_DISK', 'local'),

        // Supported SCORM versions
        'supported_versions' => ['1.2', '2004'],
    ],

    /*
    |--------------------------------------------------------------------------
    | xAPI (Experience API) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for xAPI statement recording and LRS.
    |
    */

    'xapi' => [
        // Whether to auto-record xAPI statements from domain events
        'auto_record' => env('LMS_XAPI_AUTO_RECORD', true),

        // Base IRI for activity identifiers
        'activity_base_iri' => env('LMS_XAPI_ACTIVITY_IRI', env('APP_URL', 'http://enteraksi.test')),
    ],

];
