import type {
    ContentType,
    DifficultyLevel,
    CourseStatus,
    EnrollmentStatus,
    AssessmentStatus,
    AttemptStatus,
    CourseVisibility,
} from '@/types';

/**
 * Application-wide constants
 */

// =============================================================================
// Pagination
// =============================================================================

export const DEFAULT_PAGE_SIZE = 10;
export const PAGE_SIZE_OPTIONS = [10, 25, 50, 100] as const;

// =============================================================================
// File Upload Limits
// =============================================================================

export const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
export const MAX_VIDEO_SIZE = 500 * 1024 * 1024; // 500MB
export const MAX_AUDIO_SIZE = 50 * 1024 * 1024; // 50MB
export const MAX_DOCUMENT_SIZE = 20 * 1024 * 1024; // 20MB
export const MAX_THUMBNAIL_SIZE = 2 * 1024 * 1024; // 2MB

// =============================================================================
// Allowed File Types
// =============================================================================

export const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] as const;
export const ALLOWED_IMAGE_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.gif', '.webp'] as const;

export const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/ogg'] as const;
export const ALLOWED_VIDEO_EXTENSIONS = ['.mp4', '.webm', '.ogg'] as const;

export const ALLOWED_AUDIO_TYPES = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'] as const;
export const ALLOWED_AUDIO_EXTENSIONS = ['.mp3', '.wav', '.ogg'] as const;

export const ALLOWED_DOCUMENT_TYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
] as const;
export const ALLOWED_DOCUMENT_EXTENSIONS = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx'] as const;

// =============================================================================
// Tones — the semantic palette from the "Tenang" design (ADR 007)
// =============================================================================

/**
 * Every coloured status surface in the app resolves to one of these tones.
 *
 * Each tone pairs a `*-soft` background with its full-strength ink, which is
 * how Tenang builds badges. The tokens behind them are redefined under `.dark`,
 * so a tone carries no `dark:` variant of its own -- do not add one.
 *
 * Meanings are fixed; pick the tone that matches the meaning, not the hue:
 * - `neutral` — inert, unpublished, or locked. Nothing to react to.
 * - `primary` — the pine brand accent. Identity, not status.
 * - `info`    — live and in good standing, but not finished.
 * - `warn`    — needs someone's attention.
 * - `danger`  — ended badly, or withdrawn.
 * - `ok`      — succeeded.
 * - `gold`    — achievement. Reserved for what a learner *earns*.
 */
export const TONES = {
    neutral: { bg: 'bg-surface-2', text: 'text-muted-foreground', border: 'border-transparent', icon: 'text-subtle' },
    primary: { bg: 'bg-primary-soft', text: 'text-primary', border: 'border-transparent', icon: 'text-primary' },
    info: { bg: 'bg-info-soft', text: 'text-info', border: 'border-transparent', icon: 'text-info' },
    warn: { bg: 'bg-warn-soft', text: 'text-warn', border: 'border-transparent', icon: 'text-warn' },
    danger: { bg: 'bg-danger-soft', text: 'text-danger', border: 'border-transparent', icon: 'text-danger' },
    ok: { bg: 'bg-ok-soft', text: 'text-ok', border: 'border-transparent', icon: 'text-ok' },
    gold: { bg: 'bg-gold-soft', text: 'text-gold', border: 'border-transparent', icon: 'text-gold' },
} as const;

export type Tone = keyof typeof TONES;

/**
 * Flatten a tone to the `bg + text` class string that badge helpers return.
 * An unrecognised status falls back to `neutral` rather than to nothing, so a
 * badge never renders as unstyled text.
 */
export function toneClasses(tone: Tone | undefined): string {
    const { bg, text } = TONES[tone ?? 'neutral'];

    return `${bg} ${text}`;
}

// =============================================================================
// Status Colors
// =============================================================================

export const COURSE_STATUS_COLORS: Record<CourseStatus, { bg: string; text: string; border: string }> = {
    draft: TONES.neutral,
    published: TONES.ok,
    archived: TONES.danger,
};

export const ENROLLMENT_STATUS_COLORS: Record<EnrollmentStatus, { bg: string; text: string; border: string }> = {
    active: TONES.info,
    /** Gold, not ok: finishing a Course is the achievement the learner earns. */
    completed: TONES.gold,
    dropped: TONES.danger,
};

export const DIFFICULTY_COLORS: Record<DifficultyLevel, { bg: string; text: string; border: string }> = {
    beginner: TONES.ok,
    intermediate: TONES.warn,
    advanced: TONES.danger,
};

export const ASSESSMENT_STATUS_COLORS: Record<AssessmentStatus, { bg: string; text: string; border: string }> = {
    draft: TONES.neutral,
    published: TONES.ok,
    archived: TONES.danger,
};

export const ATTEMPT_STATUS_COLORS: Record<AttemptStatus, { bg: string; text: string; border: string }> = {
    in_progress: TONES.info,
    /** Awaiting a grader — the one attempt state that needs someone to act. */
    submitted: TONES.warn,
    graded: TONES.ok,
    completed: TONES.ok,
};

export const VISIBILITY_COLORS: Record<CourseVisibility, { bg: string; text: string; border: string }> = {
    public: TONES.ok,
    restricted: TONES.warn,
    hidden: TONES.neutral,
};

// =============================================================================
// Content Type Colors
// =============================================================================

/**
 * Lesson forms. These are categories rather than statuses, so they borrow the
 * tone palette purely to stay distinguishable -- `warn` on a document does not
 * mean anything is wrong.
 */
export const CONTENT_TYPE_COLORS: Record<ContentType, { bg: string; text: string; icon: string }> = {
    text: TONES.neutral,
    video: TONES.primary,
    youtube: TONES.danger,
    audio: TONES.info,
    document: TONES.warn,
    conference: TONES.ok,
};

// =============================================================================
// Local Storage Keys
// =============================================================================

export const STORAGE_KEYS = {
    theme: 'enterlms-theme',
    sidebarCollapsed: 'enterlms-sidebar-collapsed',
    recentCourses: 'enterlms-recent-courses',
    videoProgress: 'enterlms-video-progress',
    audioProgress: 'enterlms-audio-progress',
    lessonProgress: 'enterlms-lesson-progress',
    tutorOpen: 'enterlms-tutor-open',
    tutorGeometry: 'enterlms-tutor-geometry',
    tutorFollow: 'enterlms-tutor-follow',
} as const;

// =============================================================================
// Timing Constants
// =============================================================================

/** Debounce delays in milliseconds */
export const DEBOUNCE = {
    search: 300,
    autosave: 1000,
    resize: 100,
    input: 150,
} as const;

/** Throttle limits in milliseconds */
export const THROTTLE = {
    scroll: 100,
    resize: 200,
    videoProgress: 5000,
} as const;

/** Animation durations in milliseconds */
export const ANIMATION = {
    fast: 150,
    normal: 300,
    slow: 500,
} as const;

/** Toast duration in milliseconds */
export const TOAST_DURATION = {
    short: 3000,
    normal: 5000,
    long: 8000,
} as const;

// =============================================================================
// Assessment Constants
// =============================================================================

export const DEFAULT_PASSING_SCORE = 70;
export const DEFAULT_MAX_ATTEMPTS = 3;
export const DEFAULT_TIME_LIMIT_MINUTES = 60;

// =============================================================================
// Course Constants
// =============================================================================

export const DEFAULT_LESSON_DURATION_MINUTES = 30;
export const MIN_COMPLETION_PERCENTAGE = 80;

// =============================================================================
// Breakpoints (matching Tailwind)
// =============================================================================

export const BREAKPOINTS = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
} as const;
