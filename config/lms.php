<?php

use App\Domain\Shared\AcademyPresetCatalog;

$lmsBoolEnv = static function (string $key): ?bool {
    $raw = env($key);

    if ($raw === null || $raw === '') {
        return null;
    }

    return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
};

$lmsStringEnv = static function (string $key): ?string {
    $raw = env($key);

    if ($raw === null || $raw === '') {
        return null;
    }

    return (string) $raw;
};

$academy = AcademyPresetCatalog::resolve(
    (string) env('LMS_PRESET', AcademyPresetCatalog::DEFAULT),
    [
        'offerings' => $lmsBoolEnv('LMS_FEATURE_OFFERINGS'),
        'facilitators' => $lmsBoolEnv('LMS_FEATURE_FACILITATORS'),
        'attendance' => $lmsBoolEnv('LMS_FEATURE_ATTENDANCE'),
        'letter_grades' => $lmsBoolEnv('LMS_FEATURE_LETTER_GRADES'),
        'academic_calendar' => $lmsBoolEnv('LMS_FEATURE_ACADEMIC_CALENDAR'),
        'sso' => $lmsBoolEnv('LMS_FEATURE_SSO'),
    ],
    [
        'offering' => $lmsStringEnv('LMS_LABEL_OFFERING'),
        'facilitator' => $lmsStringEnv('LMS_LABEL_FACILITATOR'),
        'learner' => $lmsStringEnv('LMS_LABEL_LEARNER'),
    ],
    [
        'scheme' => $lmsStringEnv('LMS_IDENTITY_SCHEME'),
        'label' => $lmsStringEnv('LMS_IDENTITY_LABEL'),
    ],
);

return [

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
    | - 'internal': All courses are free
    | - 'commercial': Courses can carry a price
    |
    | A priced Course has no self-serve path; LMS Admin grants Enrollment.
    | This is not flavor (ADR 010). Flavor is the preset below.
    |
    */

    'mode' => env('LMS_MODE', 'internal'),

    /*
    |--------------------------------------------------------------------------
    | Install preset (ADR 010)
    |--------------------------------------------------------------------------
    |
    | One client, one installation. Presets turn capabilities on.
    | Domain code uses Academy::enabled() / Academy::label(), never
    | compares preset names. Env LMS_FEATURE_* and LMS_LABEL_* override
    | individual keys; unset means "use the preset".
    |
    */

    'preset' => $academy['preset'],

    'features' => $academy['features'],

    'labels' => $academy['labels'],

    'identity' => $academy['identity'],

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
        'activity_base_iri' => env('LMS_XAPI_ACTIVITY_IRI', env('APP_URL', 'http://enterlms.test')),
    ],

];
