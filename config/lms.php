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

    // Banking/compliance default: lessons + required assessments gate completion.
    'progress_calculator' => env('LMS_PROGRESS_CALCULATOR', 'assessment_inclusive'),

    /*
    |--------------------------------------------------------------------------
    | Eager loading strictness (RequiresEagerLoading trait)
    |--------------------------------------------------------------------------
    |
    | true  = throw on missing withCount/withAvg (default — fail closed)
    | false = log + fallback query (emergency only)
    |
    */
    'strict_eager_loading' => filter_var(
        env('LMS_STRICT_EAGER_LOADING', true),
        FILTER_VALIDATE_BOOLEAN
    ),

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
    | Even in commercial mode, payments stay off until payment.enabled=true
    | AND a PaymentGatewayContract is bound (see PaymentService).
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
        // Hard-off until B-001 gateway ships. Commercial mode alone is not enough.
        'enabled' => env('LMS_PAYMENT_ENABLED', false),
        'gateway' => env('LMS_PAYMENT_GATEWAY', null),
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
