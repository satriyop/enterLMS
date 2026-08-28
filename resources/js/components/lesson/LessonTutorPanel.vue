<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { store as storeTurn } from '@/actions/App/Http/Controllers/ConversationTurnController';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
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

const open = ref(false);
const threadEl = ref<HTMLElement | null>(null);

const tutorForm = useForm({
    message: '',
});

const submitTutorTurn = () => {
    tutorForm.post(storeTurn.url({ course: props.courseId, lesson: props.lessonId }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            tutorForm.reset('message');
            scrollThread();
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
    <div class="pointer-events-none absolute right-4 bottom-4 z-20 flex flex-col items-end gap-3">
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
                    <p v-if="conversation.turns.length === 0" class="text-sm text-muted-foreground">
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
                    <Button type="submit" class="w-full" :disabled="tutorForm.processing">
                        Kirim
                    </Button>
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
