<script setup lang="ts">
// =============================================================================
// StatusBadge Component
// A unified badge component for displaying status across different entity types
// =============================================================================

import { computed } from 'vue';
import type {
    CourseStatus,
    EnrollmentStatus,
    DifficultyLevel,
    AssessmentStatus,
    AttemptStatus,
    CourseVisibility,
} from '@/types';
import {
    COURSE_STATUS_COLORS,
    ENROLLMENT_STATUS_COLORS,
    DIFFICULTY_COLORS,
    ASSESSMENT_STATUS_COLORS,
    ATTEMPT_STATUS_COLORS,
    VISIBILITY_COLORS,
    TONES,
} from '@/lib/constants';
import {
    courseStatusLabel,
    enrollmentStatusLabel,
    difficultyLabel,
    assessmentStatusLabel,
    attemptStatusLabel,
    visibilityLabel,
} from '@/lib/formatters';

// =============================================================================
// Types
// =============================================================================

type StatusType =
    | 'course'
    | 'enrollment'
    | 'difficulty'
    | 'assessment'
    | 'attempt'
    | 'visibility';

type StatusValue =
    | CourseStatus
    | EnrollmentStatus
    | DifficultyLevel
    | AssessmentStatus
    | AttemptStatus
    | CourseVisibility;

interface Props {
    /** The type of status to display */
    type: StatusType;
    /** The status value */
    status: StatusValue;
    /** Size variant */
    size?: 'sm' | 'md' | 'lg';
    /** Whether to show icon (if applicable) */
    showIcon?: boolean;
}

// =============================================================================
// Component Setup
// =============================================================================

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showIcon: false,
});

// =============================================================================
// Computed Properties
// =============================================================================

const colorClasses = computed(() => {
    const defaultColors = TONES.neutral;

    switch (props.type) {
        case 'course':
            return COURSE_STATUS_COLORS[props.status as CourseStatus] ?? defaultColors;
        case 'enrollment':
            return ENROLLMENT_STATUS_COLORS[props.status as EnrollmentStatus] ?? defaultColors;
        case 'difficulty':
            return DIFFICULTY_COLORS[props.status as DifficultyLevel] ?? defaultColors;
        case 'assessment':
            return ASSESSMENT_STATUS_COLORS[props.status as AssessmentStatus] ?? defaultColors;
        case 'attempt':
            return ATTEMPT_STATUS_COLORS[props.status as AttemptStatus] ?? defaultColors;
        case 'visibility':
            return VISIBILITY_COLORS[props.status as CourseVisibility] ?? defaultColors;
        default:
            return defaultColors;
    }
});

const label = computed(() => {
    switch (props.type) {
        case 'course':
            return courseStatusLabel(props.status as CourseStatus);
        case 'enrollment':
            return enrollmentStatusLabel(props.status as EnrollmentStatus);
        case 'difficulty':
            return difficultyLabel(props.status as DifficultyLevel);
        case 'assessment':
            return assessmentStatusLabel(props.status as AssessmentStatus);
        case 'attempt':
            return attemptStatusLabel(props.status as AttemptStatus);
        case 'visibility':
            return visibilityLabel(props.status as CourseVisibility);
        default:
            return String(props.status);
    }
});

/** Tenang's `.badge` scale: tighter and a shade smaller than shadcn's default. */
const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-[0.45rem] py-[0.1rem] text-[0.68rem]';
        case 'lg':
            return 'px-[0.7rem] py-[0.28rem] text-[0.8rem]';
        case 'md':
        default:
            return 'px-[0.55rem] py-[0.16rem] text-[0.72rem]';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-[0.3rem] rounded-pill border border-transparent font-[550] whitespace-nowrap',
            colorClasses.bg,
            colorClasses.text,
            sizeClasses,
        ]"
    >
        <slot name="icon" />
        {{ label }}
    </span>
</template>
