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
    type Ref,
} from 'vue';

export type ResizeDirection = 'n' | 'e' | 's' | 'w' | 'ne' | 'se' | 'sw' | 'nw';

export type TutorMode = 'float' | 'dock-left' | 'dock-right';

export interface TutorGeometry {
    left: number;
    top: number;
    width: number;
    height: number;
    /**
     * Docking is not a different component. It is the same window with one
     * axis surrendered to an edge, so it rides along in the same record.
     */
    mode: TutorMode;
    collapsed: boolean;
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
    dockMinWidth: 20, // --tutor-dock-w-min
    dockMaxWidth: 38, // --tutor-dock-w-max
} as const;

/** `--tutor-snap`, the one bound the design states in px rather than rem. */
const SNAP_PX = 28;

/** Below this the design abandons the floating window for a sheet. */
const SHEET_QUERY = '(max-width: 640px)';

const MODES: TutorMode[] = ['float', 'dock-left', 'dock-right'];

const clamp = (value: number, min: number, max: number): number =>
    Math.min(Math.max(value, min), Math.max(min, max));

const rem = (value: number): number =>
    value * parseFloat(getComputedStyle(document.documentElement).fontSize);

/**
 * First run parks the window in the reading column rather than the viewport
 * corner, using whatever the caller anchors it to — in practice the launcher,
 * which sits in the Lesson by construction. Anchoring beats a hardcoded inset
 * because the Lesson's sidebar can be closed, and it keeps the window off the
 * course progress that lives in the viewport's bottom-right.
 */
const defaultGeometry = (anchor: HTMLElement | null): TutorGeometry => {
    const width = rem(BOUNDS.width);
    const height = Math.min(rem(BOUNDS.height), window.innerHeight - rem(8));
    const rect = anchor?.getBoundingClientRect();

    if (!rect || rect.right <= 0) {
        return {
            width,
            height,
            left: window.innerWidth - width - rem(BOUNDS.gutter),
            top: window.innerHeight - height - rem(BOUNDS.gutter),
            mode: 'float',
            collapsed: false,
        };
    }

    return {
        width,
        height,
        left: rect.right - width,
        top: rect.top,
        mode: 'float',
        collapsed: false,
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

    /**
     * A docked window has no free axis to be pushed off — CSS pins it to the
     * edge and to full height — so only its width is worth bounding.
     */
    if (geometry.mode !== 'float') {
        return {
            ...geometry,
            width: clamp(
                geometry.width,
                rem(BOUNDS.dockMinWidth),
                Math.min(rem(BOUNDS.dockMaxWidth), window.innerWidth - gutter),
            ),
        };
    }

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
        ...geometry,
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

const readGeometry = (fallback: TutorGeometry): TutorGeometry | null => {
    try {
        const raw = localStorage.getItem(STORAGE_KEYS.tutorGeometry);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Partial<TutorGeometry>;

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
            mode: MODES.includes(parsed.mode as TutorMode)
                ? (parsed.mode as TutorMode)
                : fallback.mode,
            collapsed: parsed.collapsed === true,
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
export function useTutorWindow(
    anchor: Ref<HTMLElement | null>,
    options: { onDismiss?: () => void } = {},
) {
    const geometry = ref<TutorGeometry>({
        left: 0,
        top: 0,
        width: 0,
        height: 0,
    });
    const isDragging = ref(false);
    const isResizing = ref(false);

    /** Which edge the current drag would dock to, or null. Drives the hint. */
    const snapTarget = ref<'left' | 'right' | null>(null);

    /**
     * A phone gets a sheet, not a window, and the sheet is pinned by CSS that
     * outranks the inline geometry. Dragging under it would silently rewrite a
     * position the Learner cannot see changing, so the gestures stand down.
     */
    const isSheet = ref(false);

    /** How far the sheet has been pulled down, mid-gesture. */
    const sheetOffset = ref(0);

    const mode = computed(() => geometry.value.mode);
    const isDocked = computed(() => geometry.value.mode !== 'float');
    const isCollapsed = computed(() => geometry.value.collapsed);

    const style = computed<CSSProperties>(() => {
        /**
         * A docked window is positioned entirely by CSS — pinned to its edge
         * and to full height — so emitting left/top/height here would just be
         * dead declarations the stylesheet has to fight.
         */
        if (geometry.value.mode !== 'float') {
            return { width: `${Math.round(geometry.value.width)}px` };
        }

        return {
            left: `${Math.round(geometry.value.left)}px`,
            top: `${Math.round(geometry.value.top)}px`,
            width: `${Math.round(geometry.value.width)}px`,
            height: `${Math.round(geometry.value.height)}px`,
        };
    });

    /**
     * The sheet is pinned by CSS, so its one gesture rides on a transform
     * instead of on geometry — nothing here is persisted, because pulling a
     * sheet down is a way of closing it, not a way of placing it.
     */
    const sheetStyle = computed<CSSProperties>(() =>
        sheetOffset.value > 0
            ? { transform: `translateY(${Math.round(sheetOffset.value)}px)` }
            : {},
    );

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
     * Surrender one axis to an edge. A docked window keeps its width, because
     * that is the one thing the Learner still gets to choose about it, but
     * within the narrower band a full-height column reads well at.
     */
    const dock = (side: 'left' | 'right'): void => {
        geometry.value = {
            ...geometry.value,
            mode: side === 'left' ? 'dock-left' : 'dock-right',
            collapsed: false,
        };

        commit();
    };

    /**
     * Undocking has to invent a position, because a docked window does not
     * have one. Park it just inside the edge it was clinging to so it appears
     * where the Learner last saw it rather than jumping across the screen.
     */
    const undock = (): void => {
        const gutter = rem(BOUNDS.gutter);
        const wasLeft = geometry.value.mode === 'dock-left';

        geometry.value = {
            ...geometry.value,
            mode: 'float',
            left: wasLeft
                ? gutter
                : window.innerWidth - geometry.value.width - gutter,
            top: rem(4),
            height: Math.min(rem(BOUNDS.height), window.innerHeight - rem(8)),
        };

        commit();
    };

    const toggleDock = (): void => {
        if (isDocked.value) {
            undock();
            return;
        }

        /**
         * Dock to whichever half the window already sits in, so the button
         * moves it the shorter distance and the result is not a surprise.
         */
        const centre = geometry.value.left + geometry.value.width / 2;

        dock(centre > window.innerWidth / 2 ? 'right' : 'left');
    };

    /**
     * Collapsing is not closing. The window becomes its own title bar and
     * parks; the Conversation is still there, and one more click brings it
     * back. Escape reaches for this rather than for the close button.
     */
    const toggleCollapse = (): void => {
        geometry.value = {
            ...geometry.value,
            collapsed: !geometry.value.collapsed,
        };

        commit();
    };

    const collapse = (): void => {
        if (geometry.value.collapsed) {
            return;
        }

        toggleCollapse();
    };

    /** How close to an edge a drag has to end before it docks instead. */
    const snapSideAt = (x: number): 'left' | 'right' | null => {
        if (x <= SNAP_PX) {
            return 'left';
        }

        if (x >= window.innerWidth - SNAP_PX) {
            return 'right';
        }

        return null;
    };

    /**
     * Move and up are bound to the document, not to the handle. Pointer capture
     * would be tidier, but a pointer that leaves the handle faster than the
     * window can follow — which happens on every quick throw toward an edge —
     * stops delivering events to the captured element in some engines, and the
     * window is left stuck mid-flight.
     */
    /**
     * On a phone the header is not a move handle — there is nowhere to move
     * to — so it becomes the one gesture the platform already taught: pull
     * down to dismiss. Past a quarter of its own height the sheet goes; short
     * of that it springs back, so a hesitant pull is not a closed Conversation.
     */
    const startSheetDismiss = (event: PointerEvent): void => {
        const startY = event.clientY;
        const height = (event.currentTarget as HTMLElement)
            .closest('[data-mode]')
            ?.getBoundingClientRect().height;

        const onMove = (moveEvent: PointerEvent): void => {
            sheetOffset.value = Math.max(0, moveEvent.clientY - startY);
        };

        const onEnd = (): void => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onEnd);
            document.removeEventListener('pointercancel', onEnd);

            const travelled = sheetOffset.value;

            sheetOffset.value = 0;

            if (height && travelled > height * 0.25) {
                options.onDismiss?.();
            }
        };

        document.addEventListener('pointermove', onMove);
        document.addEventListener('pointerup', onEnd);
        document.addEventListener('pointercancel', onEnd);
    };

    const startDrag = (event: PointerEvent): void => {
        if (event.button !== 0) {
            return;
        }

        if ((event.target as HTMLElement).closest('button')) {
            return;
        }

        if (isSheet.value) {
            startSheetDismiss(event);
            return;
        }

        /**
         * A docked window has no free axis, so the header is not a handle.
         * Undocking first is the deliberate act that gives it one back.
         */
        if (isDocked.value) {
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

            snapTarget.value = snapSideAt(moveEvent.clientX);
        };

        const onEnd = (endEvent: PointerEvent): void => {
            isDragging.value = false;
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onEnd);
            document.removeEventListener('pointercancel', onEnd);

            /**
             * Dragging to an edge is how most people will ever discover
             * docking, so the release honours the hint that was showing.
             */
            const side = snapSideAt(endEvent.clientX);

            snapTarget.value = null;

            if (side) {
                dock(side);
                return;
            }

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
        if (event.button !== 0 || isSheet.value) {
            return;
        }

        event.preventDefault();

        const docked = isDocked.value;
        const minWidth = rem(docked ? BOUNDS.dockMinWidth : BOUNDS.minWidth);
        const maxWidth = rem(docked ? BOUNDS.dockMaxWidth : BOUNDS.maxWidth);
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
            next.width = clamp(next.width, minWidth, maxWidth);
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
        if (isSheet.value) {
            return;
        }

        if (event.key === 'Home' || event.key === 'End') {
            event.preventDefault();
            dock(event.key === 'Home' ? 'left' : 'right');
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleCollapse();
            return;
        }

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
            const docked = isDocked.value;

            geometry.value = {
                ...geometry.value,
                width: clamp(
                    geometry.value.width + move[0],
                    rem(docked ? BOUNDS.dockMinWidth : BOUNDS.minWidth),
                    rem(docked ? BOUNDS.dockMaxWidth : BOUNDS.maxWidth),
                ),
                height: clamp(
                    geometry.value.height + move[1],
                    rem(BOUNDS.minHeight),
                    window.innerHeight,
                ),
            };
        } else {
            /** Nudging a docked window would move something CSS has pinned. */
            if (isDocked.value) {
                return;
            }

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

    let sheetQuery: MediaQueryList | null = null;

    const onSheetChange = (
        event: MediaQueryListEvent | MediaQueryList,
    ): void => {
        isSheet.value = event.matches;
    };

    onMounted(() => {
        /**
         * Restore, then reflow rather than commit. A geometry saved on a wider
         * monitor paints within reach here without losing the position it was
         * saved at, so the same Learner on the same big screen tomorrow gets
         * their window back where they left it.
         */
        const fallback = defaultGeometry(anchor.value);

        geometry.value = readGeometry(fallback) ?? fallback;
        reflow();

        sheetQuery = window.matchMedia(SHEET_QUERY);
        onSheetChange(sheetQuery);
        sheetQuery.addEventListener('change', onSheetChange);

        window.addEventListener('resize', onViewportResize);
    });

    onBeforeUnmount(() => {
        sheetQuery?.removeEventListener('change', onSheetChange);
        window.removeEventListener('resize', onViewportResize);
    });

    return {
        geometry,
        style,
        sheetStyle,
        mode,
        isDocked,
        isCollapsed,
        isDragging,
        isResizing,
        isSheet,
        snapTarget,
        startDrag,
        startResize,
        onHeaderKeydown,
        dock,
        undock,
        toggleDock,
        toggleCollapse,
        collapse,
    };
}
