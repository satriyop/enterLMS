// =============================================================================
// useTutorWindow Composable
// Drag, resize and remember the Tutor overlay's geometry
// Design source: docs/design/tutor-overlay/
// =============================================================================

import { STORAGE_KEYS } from '@/lib/constants';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    type CSSProperties,
} from 'vue';

export type ResizeDirection = 'n' | 'e' | 's' | 'w' | 'ne' | 'se' | 'sw' | 'nw';

export interface TutorGeometry {
    left: number;
    top: number;
    width: number;
    height: number;
}

/**
 * Mirrors the geometry tokens in `resources/css/app.css`. The CSS owns how the
 * window looks; the clamp maths needs the same numbers to stop a resize past
 * the minimum from walking the opposite edge across the screen, and reparsing
 * custom properties on every pointermove is not worth the fidelity.
 */
const BOUNDS = {
    width: 24, // --tutor-w
    height: 30, // --tutor-h
    minWidth: 19, // --tutor-w-min
    minHeight: 14, // --tutor-h-min
    maxWidth: 44, // --tutor-w-max
    gutter: 1, // --tutor-gutter
    keepVisible: 6, // --tutor-keep-visible
    headerHeight: 3, // --tutor-header-h
    launcher: 3.5, // --tutor-launcher-h plus its gutter
} as const;

const clamp = (value: number, min: number, max: number): number =>
    Math.min(Math.max(value, min), Math.max(min, max));

const rem = (value: number): number =>
    value * parseFloat(getComputedStyle(document.documentElement).fontSize);

/**
 * First run parks the window above its own launcher rather than on top of it:
 * the Learner has to be able to see the control they just used.
 */
const defaultGeometry = (): TutorGeometry => {
    const width = rem(BOUNDS.width);
    const height = Math.min(rem(BOUNDS.height), window.innerHeight - rem(8));

    return {
        width,
        height,
        left: window.innerWidth - width - rem(BOUNDS.gutter),
        top:
            window.innerHeight -
            height -
            rem(BOUNDS.gutter) -
            rem(BOUNDS.launcher),
    };
};

/**
 * A window that remembers a position from a wider monitor must not restore
 * off-screen. Clamp keeps at least `--tutor-keep-visible` of the header
 * reachable on every axis, so the Learner can always grab it back.
 */
const clampToViewport = (geometry: TutorGeometry): TutorGeometry => {
    const gutter = rem(BOUNDS.gutter);
    const keep = rem(BOUNDS.keepVisible);

    const width = clamp(
        geometry.width,
        rem(BOUNDS.minWidth),
        Math.min(rem(BOUNDS.maxWidth), window.innerWidth - gutter * 2),
    );
    const height = clamp(
        geometry.height,
        rem(BOUNDS.minHeight),
        window.innerHeight - gutter * 2,
    );

    return {
        width,
        height,
        left: clamp(geometry.left, keep - width, window.innerWidth - keep),
        top: clamp(
            geometry.top,
            0,
            window.innerHeight - rem(BOUNDS.headerHeight),
        ),
    };
};

const readGeometry = (): TutorGeometry | null => {
    try {
        const raw = localStorage.getItem(STORAGE_KEYS.tutorGeometry);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Partial<TutorGeometry>;
        const fallback = defaultGeometry();

        return {
            left: Number.isFinite(parsed.left)
                ? (parsed.left as number)
                : fallback.left,
            top: Number.isFinite(parsed.top)
                ? (parsed.top as number)
                : fallback.top,
            width: Number.isFinite(parsed.width)
                ? (parsed.width as number)
                : fallback.width,
            height: Number.isFinite(parsed.height)
                ? (parsed.height as number)
                : fallback.height,
        };
    } catch {
        return null;
    }
};

const persistGeometry = (geometry: TutorGeometry): void => {
    try {
        localStorage.setItem(
            STORAGE_KEYS.tutorGeometry,
            JSON.stringify(geometry),
        );
    } catch {
        // Private mode: the window still works, it just forgets.
    }
};

/**
 * Position and size are a workspace preference, so they live in localStorage
 * and outlive the Lesson. Whether the overlay was *open* is about this Lesson
 * and stays in sessionStorage, which is the split the panel already makes.
 */
export function useTutorWindow() {
    const geometry = ref<TutorGeometry>({
        left: 0,
        top: 0,
        width: 0,
        height: 0,
    });
    const isDragging = ref(false);
    const isResizing = ref(false);

    const style = computed<CSSProperties>(() => ({
        left: `${Math.round(geometry.value.left)}px`,
        top: `${Math.round(geometry.value.top)}px`,
        width: `${Math.round(geometry.value.width)}px`,
        height: `${Math.round(geometry.value.height)}px`,
    }));

    /**
     * Bring the window back within reach without recording that we did. Used
     * for every move the Learner did not ask for.
     */
    const reflow = (): void => {
        geometry.value = clampToViewport(geometry.value);
    };

    /**
     * Reflow, then remember. Only a deliberate gesture — a drag, a resize, an
     * arrow key — is allowed to write geometry, so a viewport the Learner is
     * merely passing through never overwrites the one they chose.
     */
    const commit = (): void => {
        reflow();
        persistGeometry(geometry.value);
    };

    /**
     * Move and up are bound to the document, not to the handle. Pointer capture
     * would be tidier, but a pointer that leaves the handle faster than the
     * window can follow — which happens on every quick throw toward an edge —
     * stops delivering events to the captured element in some engines, and the
     * window is left stuck mid-flight.
     */
    const startDrag = (event: PointerEvent): void => {
        if (event.button !== 0) {
            return;
        }

        if ((event.target as HTMLElement).closest('button')) {
            return;
        }

        event.preventDefault();

        const offsetX = event.clientX - geometry.value.left;
        const offsetY = event.clientY - geometry.value.top;

        const onMove = (moveEvent: PointerEvent): void => {
            geometry.value = {
                ...geometry.value,
                left: moveEvent.clientX - offsetX,
                top: moveEvent.clientY - offsetY,
            };
        };

        const onEnd = (): void => {
            isDragging.value = false;
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onEnd);
            document.removeEventListener('pointercancel', onEnd);
            commit();
        };

        isDragging.value = true;
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onEnd);
        document.addEventListener('pointercancel', onEnd);
    };

    const startResize = (
        event: PointerEvent,
        direction: ResizeDirection,
    ): void => {
        if (event.button !== 0) {
            return;
        }

        event.preventDefault();

        const start = { x: event.clientX, y: event.clientY, ...geometry.value };

        const onMove = (moveEvent: PointerEvent): void => {
            const dx = moveEvent.clientX - start.x;
            const dy = moveEvent.clientY - start.y;

            const next = { ...start } as TutorGeometry;

            if (direction.includes('e')) {
                next.width = start.width + dx;
            }

            if (direction.includes('s')) {
                next.height = start.height + dy;
            }

            if (direction.includes('w')) {
                next.width = start.width - dx;
            }

            if (direction.includes('n')) {
                next.height = start.height - dy;
            }

            /**
             * Clamp the size first, then re-derive the moving edge from it.
             * Doing it the other way round lets a drag past the minimum walk
             * the opposite edge across the screen.
             */
            next.width = clamp(
                next.width,
                rem(BOUNDS.minWidth),
                rem(BOUNDS.maxWidth),
            );
            next.height = clamp(
                next.height,
                rem(BOUNDS.minHeight),
                window.innerHeight - rem(2),
            );

            if (direction.includes('w')) {
                next.left = start.left + (start.width - next.width);
            }

            if (direction.includes('n')) {
                next.top = start.top + (start.height - next.height);
            }

            geometry.value = next;
        };

        const onEnd = (): void => {
            isResizing.value = false;
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onEnd);
            document.removeEventListener('pointercancel', onEnd);
            commit();
        };

        isResizing.value = true;
        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onEnd);
        document.addEventListener('pointercancel', onEnd);
    };

    /**
     * Dragging is a pointer gesture, and every pointer gesture here needs a
     * key. Without this the window is unreachable with a keyboard, which is
     * not a trade-off the academy gets to make.
     */
    const onHeaderKeydown = (event: KeyboardEvent): void => {
        const step = event.shiftKey ? 1 : 16;
        const moves: Record<string, [number, number]> = {
            ArrowLeft: [-step, 0],
            ArrowRight: [step, 0],
            ArrowUp: [0, -step],
            ArrowDown: [0, step],
        };

        const move = moves[event.key];

        if (!move) {
            return;
        }

        event.preventDefault();

        if (event.altKey) {
            geometry.value = {
                ...geometry.value,
                width: clamp(
                    geometry.value.width + move[0],
                    rem(BOUNDS.minWidth),
                    rem(BOUNDS.maxWidth),
                ),
                height: clamp(
                    geometry.value.height + move[1],
                    rem(BOUNDS.minHeight),
                    window.innerHeight,
                ),
            };
        } else {
            geometry.value = {
                ...geometry.value,
                left: geometry.value.left + move[0],
                top: geometry.value.top + move[1],
            };
        }

        commit();
    };

    /**
     * Rotating a tablet, opening devtools or dragging the browser narrower can
     * leave the overlay hanging off an edge, so a viewport change re-clamps
     * what is painted. It deliberately does not persist: the window can never
     * end up unreachable, and a viewport the Learner is only passing through
     * does not get to rewrite where they parked it. The next drag does.
     */
    const onViewportResize = (): void => {
        reflow();
    };

    onMounted(() => {
        /**
         * Restore, then reflow rather than commit. A geometry saved on a wider
         * monitor paints within reach here without losing the position it was
         * saved at, so the same Learner on the same big screen tomorrow gets
         * their window back where they left it.
         */
        geometry.value = readGeometry() ?? defaultGeometry();
        reflow();

        window.addEventListener('resize', onViewportResize);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', onViewportResize);
    });

    return {
        geometry,
        style,
        isDragging,
        isResizing,
        startDrag,
        startResize,
        onHeaderKeydown,
    };
}
