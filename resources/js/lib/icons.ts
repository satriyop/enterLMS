import type { ContentType, DifficultyLevel } from '@/types';
import {
    FileText,
    Video,
    Youtube,
    Music,
    File,
    Users,
    BookOpen,
    Award,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    AlertTriangle,
    Info,
    type LucideIcon,
} from 'lucide-vue-next';
import { TONES, type Tone } from './constants';

/**
 * Expand a tone into the `colorClass` / `bgClass` pair these helpers have
 * always returned, so callers do not have to change.
 */
function iconTone({ icon, tone }: { icon: LucideIcon; tone: Tone }): {
    icon: LucideIcon;
    colorClass: string;
    bgClass: string;
} {
    return { icon, colorClass: TONES[tone].icon, bgClass: TONES[tone].bg };
}

// =============================================================================
// Content Type Icons
// =============================================================================

/** Icon mapping for content types */
const contentTypeIconMap: Record<ContentType, LucideIcon> = {
    text: FileText,
    video: Video,
    youtube: Youtube,
    audio: Music,
    document: File,
    conference: Users,
};

/**
 * Get icon component for content type
 */
export function getContentTypeIcon(type: ContentType | string): LucideIcon {
    return contentTypeIconMap[type as ContentType] || FileText;
}

/**
 * Content type icon with color classes
 */
export function getContentTypeIconWithColor(type: ContentType | string): {
    icon: LucideIcon;
    colorClass: string;
    bgClass: string;
} {
    const config: Record<ContentType, { icon: LucideIcon; tone: Tone }> = {
        text: { icon: FileText, tone: 'neutral' },
        video: { icon: Video, tone: 'primary' },
        youtube: { icon: Youtube, tone: 'danger' },
        audio: { icon: Music, tone: 'info' },
        document: { icon: File, tone: 'warn' },
        conference: { icon: Users, tone: 'ok' },
    };
    return iconTone(config[type as ContentType] ?? { icon: FileText, tone: 'neutral' });
}

// =============================================================================
// Difficulty Icons
// =============================================================================

/** Icon mapping for difficulty levels */
const difficultyIconMap: Record<DifficultyLevel, LucideIcon> = {
    beginner: BookOpen,
    intermediate: Award,
    advanced: Award,
};

/**
 * Get icon component for difficulty level
 */
export function getDifficultyIcon(level: DifficultyLevel | string): LucideIcon {
    return difficultyIconMap[level as DifficultyLevel] || BookOpen;
}

/**
 * Difficulty icon with color classes
 */
export function getDifficultyIconWithColor(level: DifficultyLevel | string): {
    icon: LucideIcon;
    colorClass: string;
    bgClass: string;
} {
    const config: Record<DifficultyLevel, { icon: LucideIcon; tone: Tone }> = {
        beginner: { icon: BookOpen, tone: 'ok' },
        intermediate: { icon: Award, tone: 'warn' },
        advanced: { icon: Award, tone: 'danger' },
    };
    return iconTone(config[level as DifficultyLevel] ?? { icon: BookOpen, tone: 'neutral' });
}

// =============================================================================
// Status Icons
// =============================================================================

type StatusType = 'success' | 'error' | 'warning' | 'info' | 'pending';

/** Icon mapping for status types */
const statusIconMap: Record<StatusType, LucideIcon> = {
    success: CheckCircle,
    error: XCircle,
    warning: AlertTriangle,
    info: Info,
    pending: Clock,
};

/**
 * Get status icon
 */
export function getStatusIcon(status: StatusType): LucideIcon {
    return statusIconMap[status] || AlertCircle;
}

/**
 * Status icon with color classes
 */
export function getStatusIconWithColor(status: StatusType): {
    icon: LucideIcon;
    colorClass: string;
    bgClass: string;
} {
    const config: Record<StatusType, { icon: LucideIcon; tone: Tone }> = {
        success: { icon: CheckCircle, tone: 'ok' },
        error: { icon: XCircle, tone: 'danger' },
        warning: { icon: AlertTriangle, tone: 'warn' },
        info: { icon: Info, tone: 'info' },
        pending: { icon: Clock, tone: 'neutral' },
    };
    return iconTone(config[status]);
}

// =============================================================================
// Reexport Icon Components for Convenience
// =============================================================================

export {
    FileText,
    Video,
    Youtube,
    Music,
    File,
    Users,
    BookOpen,
    Award,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    AlertTriangle,
    Info,
};
