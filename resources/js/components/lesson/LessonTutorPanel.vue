<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { store as storeTurn } from '@/actions/App/Http/Controllers/ConversationTurnController';
import { store as storeFocus } from '@/actions/App/Http/Controllers/MessagingFocusController';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { STORAGE_KEYS } from '@/lib/constants';
import { useEventListener } from '@/composables/utils/useEventListener';
import { useTutorWindow, type ResizeDirection } from '@/composables/useTutorWindow';
import { ChevronDown, ChevronUp, PanelRight, PictureInPicture2, X } from 'lucide-vue-next';

interface ConversationTurn {
    id: number;
    role: 'learner' | 'tutor';
    body: string;
    created_at: string | null;
}

interface LessonConversation {
    id: number | null;
    can_post: boolean;
    turns: ConversationTurn[];
}

const props = defineProps<{
    courseId: number;
    lessonId: number;
    conversation: LessonConversation;
}>();

/**
 * Corners are listed after edges so they win the overlap and a diagonal drag
 * never degrades into a single-axis one.
 */
const resizeHandles: { dir: ResizeDirection; class: string }[] = [
    { dir: 'n', class: 'top-0 right-0 left-0 h-[var(--tutor-grip)] cursor-ns-resize' },
    { dir: 's', class: 'right-0 bottom-0 left-0 h-[var(--tutor-grip)] cursor-ns-resize' },
    { dir: 'w', class: 'top-0 bottom-0 left-0 w-[var(--tutor-grip)] cursor-ew-resize' },
    { dir: 'e', class: 'top-0 right-0 bottom-0 w-[var(--tutor-grip)] cursor-ew-resize' },
    { dir: 'nw', class: 'top-0 left-0 size-[var(--tutor-grip-touch)] cursor-nwse-resize' },
    { dir: 'ne', class: 'top-0 right-0 size-[var(--tutor-grip-touch)] cursor-nesw-resize' },
    { dir: 'sw', class: 'bottom-0 left-0 size-[var(--tutor-grip-touch)] cursor-nesw-resize' },
    { dir: 'se', class: 'right-0 bottom-0 size-[var(--tutor-grip-touch)] cursor-nwse-resize' },
];

const panelStorageKey = computed(() => `${STORAGE_KEYS.tutorOpen}-${props.lessonId}`);

const readOpen = (): boolean => {
    if (typeof sessionStorage === 'undefined') {
        return false;
    }

    return sessionStorage.getItem(panelStorageKey.value) === '1';
};

const persistOpen = (value: boolean): void => {
    if (typeof sessionStorage === 'undefined') {
        return;
    }

    sessionStorage.setItem(panelStorageKey.value, value ? '1' : '0');
};

const open = ref(false);
const threadEl = ref<HTMLElement | null>(null);

/**
 * The launcher stays in the reading column while the window teleports away, so
 * its wrapper doubles as the anchor that tells the window where the Lesson is.
 */
const anchorEl = ref<HTMLElement | null>(null);
const page = usePage();

/**
 * The window has to escape `<main>`, which clips it. Teleport does that, but it
 * has nowhere to land during SSR — Vue buffers teleported content into a slot
 * Inertia's server render never emits — so it stays in place until the
 * component is mounted, where the wrapper's fixed positioning already holds.
 * The launcher is not teleported: it is a control on this Lesson, and over in
 * the viewport corner it would sit on top of the course progress.
 */
const isMounted = ref(false);

const {
    style: windowStyle,
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
    toggleDock,
    toggleCollapse,
    collapse,
} = useTutorWindow(anchorEl, { onDismiss: () => (open.value = false) });

/**
 * Escape collapses rather than closes. A Learner reaching for it wants the
 * Lesson back, not the Conversation gone — and closing would be the one
 * action here they cannot undo with the same key.
 */
useEventListener(document, 'keydown', (event: KeyboardEvent) => {
    if (event.key !== 'Escape' || !open.value || isCollapsed.value) {
        return;
    }

    collapse();
});

onMounted(() => {
    isMounted.value = true;
    open.value = readOpen();
});

watch(open, (value) => {
    persistOpen(value);
});

const flashError = computed(() => {
    const flash = page.props.flash as { error?: string | null } | undefined;

    return flash?.error ?? null;
});

const tutorForm = useForm({
    message: '',
});

const pendingMessage = computed(() => tutorForm.message.trim());

const headerHint = computed(() => {
    if (isSheet.value) {
        return 'Tarik ke bawah untuk menutup Tutor';
    }

    if (isDocked.value) {
        return 'Tutor menempel di tepi. Home atau End untuk pindah tepi, Enter untuk menciutkan.';
    }

    return 'Pindahkan jendela Tutor. Panah untuk menggeser, Alt dan panah untuk mengubah ukuran, Home atau End untuk menempel ke tepi.';
});

const setMessagingFocus = (skin: 'whatsapp' | 'telegram') => {
    router.post(
        storeFocus.url({
            course: props.courseId,
            lesson: props.lessonId,
            skin,
        }),
        {},
        { preserveScroll: true },
    );
};

const submitTutorTurn = () => {
    open.value = true;
    persistOpen(true);

    tutorForm.post(storeTurn.url({ course: props.courseId, lesson: props.lessonId }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            tutorForm.reset('message');
            scrollThread();
        },
        onError: () => {
            open.value = true;
        },
    });
};

const scrollThread = async () => {
    await nextTick();
    if (threadEl.value) {
        threadEl.value.scrollTop = threadEl.value.scrollHeight;
    }
};

watch(
    () => props.conversation.turns.length,
    () => {
        if (open.value) {
            scrollThread();
        }
    },
);
</script>

<template>
    <div
        ref="anchorEl"
        class="pointer-events-none absolute top-4 right-4 z-[var(--tutor-z-launcher)]"
    >
        <button
            v-show="!open"
            type="button"
            class="tutor-launch pointer-events-auto"
            aria-label="Buka Tutor"
            :aria-expanded="open"
            @click="open = true"
        >
            <svg class="tutor-launch__mark" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path
                    d="M5.5 6h9A2.5 2.5 0 0 1 17 8.5v5a2.5 2.5 0 0 1-2.5 2.5H10l-3.8 3.2V16H5.5A2.5 2.5 0 0 1 3 13.5v-5A2.5 2.5 0 0 1 5.5 6Z"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linejoin="round"
                />
                <path
                    class="tutor-launch__spark"
                    d="M19 2.2 19.9 4.6 22.3 5.5 19.9 6.4 19 8.8 18.1 6.4 15.7 5.5 18.1 4.6Z"
                    fill="currentColor"
                />
            </svg>
            Tutor
        </button>
    </div>

    <Teleport to="body" :disabled="!isMounted">
        <div class="pointer-events-none fixed inset-0 z-[var(--tutor-z-overlay)]">
            <div
                v-for="side in ['left', 'right'] as const"
                :key="side"
                class="tutor-snap-hint"
                :data-side="side"
                :data-active="String(snapTarget === side)"
                aria-hidden="true"
            />

            <!-- The sheet covers the Lesson, so it needs something behind it
                 saying the Lesson is still there and one tap away. -->
            <div
                v-if="open && isSheet"
                class="pointer-events-auto fixed inset-0 z-[var(--tutor-z-sheet-scrim)] bg-[var(--tutor-scrim)]"
                aria-hidden="true"
                @click="open = false"
            />

            <section
                v-if="open"
                class="tutor-window pointer-events-auto absolute flex flex-col overflow-hidden rounded-xl border border-border bg-surface"
                :class="
                    isDragging || isResizing
                        ? 'shadow-[var(--tutor-elev-drag)] select-none'
                        : 'shadow-[var(--tutor-elev-rest)]'
                "
                :style="[windowStyle, sheetStyle]"
                :data-mode="mode"
                :data-collapsed="String(isCollapsed)"
                role="dialog"
                aria-label="Tutor"
            >
                <header
                    class="flex h-[var(--tutor-header-h)] shrink-0 items-center justify-between gap-2 border-b border-border bg-surface-2 pr-1 pl-3 select-none"
                    :class="isDocked || isSheet ? 'cursor-default' : 'cursor-grab active:cursor-grabbing'"
                    tabindex="0"
                    :aria-label="headerHint"
                    @pointerdown="startDrag"
                    @keydown="onHeaderKeydown"
                    @dblclick="toggleCollapse"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <!-- Two dots and a rule: the oldest, least ambiguous
                             "this moves" sign there is. Shown only where
                             dragging is actually possible. -->
                        <span
                            v-if="!isDocked && !isSheet"
                            class="grid shrink-0 grid-cols-2 gap-[3px] opacity-40"
                            aria-hidden="true"
                        >
                            <span v-for="dot in 6" :key="dot" class="size-[3px] rounded-full bg-foreground" />
                        </span>
                        <h2 class="truncate font-heading text-base text-foreground">Tutor</h2>
                    </div>
                    <div class="flex shrink-0 items-center">
                        <Button
                            v-if="!isSheet"
                            type="button"
                            variant="ghost"
                            size="icon"
                            :aria-label="isDocked ? 'Lepaskan Tutor dari tepi' : 'Tempelkan Tutor ke tepi'"
                            @click="toggleDock"
                        >
                            <PanelRight v-if="!isDocked" class="size-4" />
                            <PictureInPicture2 v-else class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :aria-label="isCollapsed ? 'Bentangkan Tutor' : 'Ciutkan Tutor'"
                            :aria-expanded="!isCollapsed"
                            @click="toggleCollapse"
                        >
                            <ChevronDown v-if="isCollapsed" class="size-4" />
                            <ChevronUp v-else class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            aria-label="Tutup Tutor"
                            @click="open = false"
                        >
                            <X class="size-4" />
                        </Button>
                    </div>
                </header>

                <div ref="threadEl" class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
                    <p v-if="conversation.turns.length === 0 && !tutorForm.processing" class="text-sm text-muted-foreground">
                        Belum ada percakapan. Tulis pertanyaan di bawah.
                    </p>
                    <div
                        v-for="turn in conversation.turns"
                        :key="turn.id"
                        class="rounded-md border border-border p-3"
                    >
                        <p class="text-xs font-medium text-muted-foreground">
                            {{ turn.role === 'learner' ? 'Anda' : 'Tutor' }}
                        </p>
                        <p class="mt-1 text-sm text-foreground">{{ turn.body }}</p>
                    </div>
                    <div
                        v-if="tutorForm.processing && pendingMessage"
                        class="rounded-md border border-border p-3 opacity-70"
                    >
                        <p class="text-xs font-medium text-muted-foreground">Anda</p>
                        <p class="mt-1 text-sm text-foreground">{{ pendingMessage }}</p>
                    </div>
                    <p v-if="tutorForm.processing" class="text-sm text-muted-foreground">
                        Tutor sedang menjawab…
                    </p>
                </div>

                <form
                    v-if="conversation.can_post"
                    class="shrink-0 space-y-2 border-t border-border p-4"
                    @submit.prevent="submitTutorTurn"
                >
                    <Textarea
                        v-model="tutorForm.message"
                        name="message"
                        rows="2"
                        placeholder="Tanyakan tentang Lesson ini…"
                    />
                    <p v-if="tutorForm.errors.message" class="text-sm text-destructive">
                        {{ tutorForm.errors.message }}
                    </p>
                    <p v-else-if="flashError" class="text-sm text-destructive">
                        {{ flashError }}
                    </p>
                    <Button type="submit" class="w-full" :disabled="tutorForm.processing">
                        {{ tutorForm.processing ? 'Tutor sedang menjawab…' : 'Kirim' }}
                    </Button>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            size="sm"
                            @click="setMessagingFocus('whatsapp')"
                        >
                            Lanjut di WhatsApp
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            size="sm"
                            @click="setMessagingFocus('telegram')"
                        >
                            Lanjut di Telegram
                        </Button>
                    </div>
                </form>
                <p v-else class="shrink-0 border-t border-border p-4 text-sm text-muted-foreground">
                    Percakapan ini tidak dapat ditambah.
                </p>

                <div
                    v-for="handle in resizeHandles"
                    :key="handle.dir"
                    class="absolute touch-none"
                    :class="handle.class"
                    :data-resize="handle.dir"
                    @pointerdown="startResize($event, handle.dir)"
                />
            </section>

        </div>
    </Teleport>
</template>
