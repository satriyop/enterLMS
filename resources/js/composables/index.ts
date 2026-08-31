// =============================================================================
// Composables Index
// Re-exports all composables for easy importing
// =============================================================================

// =============================================================================
// Data Composables
// For fetching and managing domain data
// =============================================================================

export { useAssessment } from './data/useAssessment';
export { useCourse } from './data/useCourse';
export { useCourses } from './data/useCourses';
export { useEnrollment } from './data/useEnrollment';
export { useLesson } from './data/useLesson';

// =============================================================================
// UI Composables
// For managing UI state
// =============================================================================

export { useConfirmation } from './ui/useConfirmation';
export { useModal } from './ui/useModal';
export { usePagination, type PaginationMeta } from './ui/usePagination';
export { useSearch } from './ui/useSearch';
export { useTabs } from './ui/useTabs';
export { useToast } from './ui/useToast';

// =============================================================================
// Utility Composables
// General-purpose composables for common patterns
// =============================================================================

export {
    useDebouncedWatch,
    useDebouncedWatchMultiple,
} from './utils/useDebouncedWatch';
export { useEventListener } from './utils/useEventListener';

// =============================================================================
// Existing Composables (Root Level)
// Legacy composables kept at root for backward compatibility
// =============================================================================

export { useAcademy } from './useAcademy';
export { useAppearance } from './useAppearance';
export { useAssessmentTimer } from './useAssessmentTimer';
export { useInitials } from './useInitials';
export { useLessonMediaProgress } from './useLessonMediaProgress';
export { useLessonPagination } from './useLessonPagination';
export { useLessonProgress } from './useLessonProgress';
export {
    useTutorWindow,
    type ResizeDirection,
    type TutorGeometry,
} from './useTutorWindow';
export { useTwoFactorAuth } from './useTwoFactorAuth';
