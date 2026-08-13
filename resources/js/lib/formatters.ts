import type {
    ContentType,
    DifficultyLevel,
    CourseStatus,
    EnrollmentStatus,
    AssessmentStatus,
    AttemptStatus,
    CourseVisibility,
    UserRole,
} from '@/types';
import { toneClasses, type Tone } from './constants';

// =============================================================================
// Duration Formatters
// =============================================================================

/**
 * Format duration from minutes to human-readable string
 * @param minutes - Duration in minutes
 * @param format - 'short' (1j 30m) | 'long' (1 jam 30 menit) | 'compact' (1.5j)
 */
export function formatDuration(
    minutes: number | null | undefined,
    format: 'short' | 'long' | 'compact' = 'short'
): string {
    if (minutes === null || minutes === undefined || minutes === 0) {
        return '-';
    }

    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    switch (format) {
        case 'long':
            if (hours === 0) return `${mins} menit`;
            if (mins === 0) return `${hours} jam`;
            return `${hours} jam ${mins} menit`;

        case 'compact':
            if (hours === 0) return `${mins}m`;
            return `${(minutes / 60).toFixed(1)}j`;

        case 'short':
        default:
            if (hours === 0) return `${mins}m`;
            if (mins === 0) return `${hours}j`;
            return `${hours}j ${mins}m`;
    }
}

/**
 * Format duration from seconds to mm:ss or hh:mm:ss format
 * Used for video/audio playback display
 */
export function formatPlaybackTime(seconds: number | null | undefined): string {
    if (seconds === null || seconds === undefined || seconds === 0) {
        return '0:00';
    }

    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hrs > 0) {
        return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${mins}:${secs.toString().padStart(2, '0')}`;
}

// =============================================================================
// Currency Formatters
// =============================================================================

/**
 * Format currency in Indonesian Rupiah
 */
export function formatCurrency(
    amount: number | null | undefined,
    options: {
        showFree?: boolean;
        compact?: boolean;
    } = {}
): string {
    const { showFree = true, compact = false } = options;

    if (amount === null || amount === undefined) {
        return '-';
    }

    if (amount === 0 && showFree) {
        return 'Gratis';
    }

    if (compact && amount >= 1_000_000) {
        return `Rp ${(amount / 1_000_000).toFixed(1)}jt`;
    }

    if (compact && amount >= 1_000) {
        return `Rp ${(amount / 1_000).toFixed(0)}rb`;
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

// =============================================================================
// File Size Formatter
// =============================================================================

/**
 * Format file size in human-readable format
 */
export function formatFileSize(bytes: number | null | undefined): string {
    if (bytes === null || bytes === undefined || bytes === 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let unitIndex = 0;
    let size = bytes;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

// =============================================================================
// Number Formatters
// =============================================================================

/**
 * Format percentage with optional decimal places
 */
export function formatPercentage(
    value: number | null | undefined,
    decimals: number = 0
): string {
    if (value === null || value === undefined) {
        return '0%';
    }
    return `${value.toFixed(decimals)}%`;
}

/**
 * Format number with thousand separators
 */
export function formatNumber(value: number | null | undefined): string {
    if (value === null || value === undefined) {
        return '0';
    }
    return new Intl.NumberFormat('id-ID').format(value);
}

/**
 * Pluralize Indonesian word (simple implementation)
 * Indonesian doesn't have plural forms, but we format count nicely
 */
export function pluralize(count: number, singular: string, plural?: string): string {
    return `${formatNumber(count)} ${plural || singular}`;
}

// =============================================================================
// Label Formatters
// =============================================================================

/**
 * Get human-readable label for difficulty level
 */
export function difficultyLabel(level: DifficultyLevel | string | null | undefined): string {
    const labels: Record<string, string> = {
        beginner: 'Pemula',
        intermediate: 'Menengah',
        advanced: 'Lanjutan',
        expert: 'Ahli',
    };
    return level ? labels[level] ?? level : '-';
}

/**
 * Get Tenang tone classes for difficulty level badge.
 *
 * `expert` shares `advanced`'s tone: Tenang has no seventh hue to spend on a
 * level the Course model does not actually expose.
 */
export function difficultyColor(level: DifficultyLevel | string | null | undefined): string {
    const tones: Record<string, Tone> = {
        beginner: 'ok',
        intermediate: 'warn',
        advanced: 'danger',
        expert: 'danger',
    };
    return level ? toneClasses(tones[level]) : '';
}

/**
 * Get human-readable label for course status
 */
export function courseStatusLabel(status: CourseStatus | string | null | undefined): string {
    const labels: Record<string, string> = {
        draft: 'Draf',
        published: 'Dipublikasikan',
        archived: 'Diarsipkan',
    };
    return status ? labels[status] ?? status : '-';
}

/**
 * Get human-readable label for course visibility
 */
export function visibilityLabel(visibility: CourseVisibility | string | null | undefined): string {
    const labels: Record<string, string> = {
        public: 'Publik',
        restricted: 'Terbatas',
        hidden: 'Tersembunyi',
    };
    return visibility ? labels[visibility] ?? visibility : '-';
}

/**
 * Get human-readable label for enrollment status
 */
export function enrollmentStatusLabel(status: EnrollmentStatus | string | null | undefined): string {
    const labels: Record<string, string> = {
        pending: 'Menunggu',
        active: 'Aktif',
        completed: 'Selesai',
        suspended: 'Ditangguhkan',
        cancelled: 'Dibatalkan',
    };
    return status ? labels[status] ?? status : '-';
}

/**
 * Get human-readable label for assessment status
 */
export function assessmentStatusLabel(status: AssessmentStatus | string | null | undefined): string {
    const labels: Record<string, string> = {
        draft: 'Draf',
        published: 'Dipublikasikan',
        archived: 'Diarsipkan',
    };
    return status ? labels[status] ?? status : '-';
}

/**
 * Get human-readable label for attempt status
 */
export function attemptStatusLabel(status: AttemptStatus | string | null | undefined): string {
    const labels: Record<string, string> = {
        in_progress: 'Sedang Dikerjakan',
        submitted: 'Telah Dikumpulkan',
        graded: 'Telah Dinilai',
        expired: 'Kadaluarsa',
    };
    return status ? labels[status] ?? status : '-';
}

/**
 * Get human-readable label for content type
 */
export function contentTypeLabel(type: ContentType | string | null | undefined): string {
    const labels: Record<string, string> = {
        text: 'Teks',
        video: 'Video',
        youtube: 'YouTube',
        audio: 'Audio',
        document: 'Dokumen',
        conference: 'Konferensi',
    };
    return type ? labels[type] ?? type : '-';
}

/**
 * Get human-readable label for question type
 */
export function questionTypeLabel(type: string | null | undefined): string {
    const labels: Record<string, string> = {
        multiple_choice: 'Pilihan Ganda',
        true_false: 'Benar/Salah',
        matching: 'Pencocokan',
        short_answer: 'Jawaban Singkat',
        essay: 'Esai',
        file_upload: 'Unggah Berkas',
    };
    return type ? labels[type] ?? type : '-';
}

// =============================================================================
// Badge Color Functions
// =============================================================================

/**
 * Get Tailwind CSS color classes for assessment/course status badge
 */
export function statusBadgeColor(status: string | null | undefined): string {
    const tones: Record<string, Tone> = {
        draft: 'neutral',
        published: 'ok',
        archived: 'danger',
    };
    return status ? toneClasses(tones[status]) : '';
}

/**
 * Get Tailwind CSS color classes for visibility badge
 */
export function visibilityBadgeColor(visibility: string | null | undefined): string {
    const tones: Record<string, Tone> = {
        public: 'ok',
        restricted: 'warn',
        hidden: 'neutral',
    };
    return visibility ? toneClasses(tones[visibility]) : '';
}

/**
 * Get Tailwind CSS color classes for attempt status badge
 */
export function attemptStatusBadgeColor(status: string | null | undefined): string {
    const tones: Record<string, Tone> = {
        in_progress: 'info',
        submitted: 'warn',
        graded: 'ok',
        completed: 'ok',
        expired: 'danger',
    };
    return status ? toneClasses(tones[status]) : '';
}

// =============================================================================
// Role Label & Badge Functions
// =============================================================================

/**
 * Get human-readable label for user role
 */
export function roleLabel(role: UserRole | string | null | undefined): string {
    const labels: Record<string, string> = {
        lms_admin: 'Admin LMS',
        learner: 'Peserta Didik',
    };
    return role ? labels[role] ?? role : '-';
}

/**
 * Get Badge component variant for user role
 */
export function roleBadgeVariant(role: UserRole | string | null | undefined): 'default' | 'secondary' | 'outline' {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
        lms_admin: 'default',
        learner: 'outline',
    };
    return role ? variants[role] ?? 'outline' : 'outline';
}

// =============================================================================
// Status Badge Variant Functions
// =============================================================================

/**
 * Get Badge component variant for draft/published/archived status.
 * Works for both course status and assessment status.
 */
export function statusBadgeVariant(status: string | null | undefined): 'default' | 'secondary' | 'outline' {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
        published: 'default',
        draft: 'secondary',
        archived: 'outline',
    };
    return status ? variants[status] ?? 'secondary' : 'secondary';
}
