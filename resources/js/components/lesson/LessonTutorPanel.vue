<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { store as storeTurn } from '@/actions/App/Http/Controllers/ConversationTurnController';
import { store as storeFocus } from '@/actions/App/Http/Controllers/MessagingFocusController';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { STORAGE_KEYS } from '@/lib/constants';
import { MessageCircle, X } from 'lucide-vue-next';

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
const page = usePage();

onMounted(() => {
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
    <div class="pointer-events-none absolute top-4 right-4 z-40 flex flex-col items-end gap-3">
            <section
                v-if="open"
                class="pointer-events-auto flex max-h-[min(60vh,28rem)] w-[min(calc(100vw-2rem),22rem)] flex-col overflow-hidden rounded-xl border border-border bg-surface shadow-lg"
                role="dialog"
                aria-label="Tutor"
            >
                <header class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3">
                    <div>
                        <h2 class="font-heading text-base text-foreground">Tutor</h2>
                        <p class="text-xs text-muted-foreground">Tanya tentang Lesson ini</p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="shrink-0"
                        aria-label="Tutup Tutor"
                        @click="open = false"
                    >
                        <X class="size-4" />
                    </Button>
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
            </section>

            <button
                v-show="!open"
                type="button"
                class="pointer-events-auto flex items-center gap-2 rounded-full bg-primary px-4 py-3 text-sm font-medium text-primary-foreground shadow-lg"
                aria-label="Buka Tutor"
                @click="open = true"
            >
                <MessageCircle class="size-5" />
                Tutor
            </button>
    </div>
</template>
