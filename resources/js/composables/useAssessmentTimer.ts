// =============================================================================
// useAssessmentTimer Composable
// Manages timer state for assessment attempts
// =============================================================================

import { ref, onUnmounted, type Ref } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface UseAssessmentTimerOptions {
    /** When the attempt started */
    startedAt: string;
    /** Time limit in minutes (null for no limit) */
    timeLimitMinutes: number | null;
    /** Callback when time runs out */
    onTimeUp?: () => void;
}

interface AssessmentTimerReturn {
    /** Formatted elapsed time (HH:MM:SS) */
    readonly timeElapsed: Ref<string>;
    /** Whether timer is running */
    readonly isTimerRunning: Ref<boolean>;
    /** Formatted time display (MM:SS for elapsed or remaining) */
    readonly formattedTime: Ref<string>;
    /** Remaining seconds (null if no limit) */
    readonly remainingSeconds: Ref<number | null>;
    /** Whether in warning zone (< 5 min remaining) */
    readonly isTimeWarning: Ref<boolean>;
    /** Whether time has expired */
    readonly isTimeExpired: Ref<boolean>;
    /** Start the timer */
    start: () => void;
    /** Stop the timer */
    stop: () => void;
}

// =============================================================================
// Composable
// =============================================================================

export function useAssessmentTimer(options: UseAssessmentTimerOptions): AssessmentTimerReturn {
    const { startedAt, timeLimitMinutes, onTimeUp } = options;

    // State
    const timeElapsed = ref('00:00:00');
    const isTimerRunning = ref(false);
    const formattedTime = ref('00:00');
    const remainingSeconds = ref<number | null>(null);
    const isTimeWarning = ref(false);
    const isTimeExpired = ref(false);

    let intervalId: ReturnType<typeof setInterval> | null = null;

    /**
     * Format seconds to HH:MM:SS
     */
    const formatTime = (totalSeconds: number): string => {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    };

    /**
     * Calculate and update time values
     */
    const calculateTime = () => {
        const startTime = new Date(startedAt);
        const now = new Date();
        const elapsedSeconds = Math.floor((now.getTime() - startTime.getTime()) / 1000);

        // Update elapsed time
        timeElapsed.value = formatTime(elapsedSeconds);

        // Update remaining time if there's a limit
        if (timeLimitMinutes) {
            const totalLimitSeconds = timeLimitMinutes * 60;
            const remaining = Math.max(0, totalLimitSeconds - elapsedSeconds);

            remainingSeconds.value = remaining;
            formattedTime.value = formatTime(remaining);
            isTimeWarning.value = remaining > 0 && remaining <= 300; // 5 minutes

            // Check if time is up
            if (remaining === 0 && !isTimeExpired.value) {
                isTimeExpired.value = true;
                onTimeUp?.();
            }
        } else {
            formattedTime.value = formatTime(elapsedSeconds);
        }
    };

    /**
     * Start the timer
     */
    const start = () => {
        if (intervalId) return;

        isTimerRunning.value = true;
        // Calculate immediately
        calculateTime();

        // Update every second
        intervalId = setInterval(calculateTime, 1000);
    };

    /**
     * Stop the timer
     */
    const stop = () => {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
            isTimerRunning.value = false;
        }
    };

    // Auto-start on creation
    start();

    // Clean up on unmount
    onUnmounted(() => {
        stop();
    });

    return {
        timeElapsed,
        isTimerRunning,
        formattedTime,
        remainingSeconds,
        isTimeWarning,
        isTimeExpired,
        start,
        stop,
    };
}
