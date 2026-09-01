<script setup lang="ts">
// =============================================================================
// Conversation Transcript
// A record being read by someone who is not a party to it. There is no
// composer here: CONTEXT.md lets an LMS Admin or Facilitator read what the
// Tutor taught, not join the Conversation.
// =============================================================================

import { index } from '@/actions/App/Http/Controllers/CourseConversationController';
import { show as courseShow } from '@/actions/App/Http/Controllers/CourseController';
import PageHeader from '@/components/crud/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDateTime } from '@/lib/date';
import { enrollmentStatusLabel } from '@/lib/formatters';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Turn {
    id: number;
    role: 'learner' | 'tutor';
    body: string;
    created_at: string | null;
}

interface Props {
    course: { id: number; title: string };
    conversation: {
        id: number;
        lesson: { id: number; title: string };
        learner: { id: number; name: string; email: string };
        offering: { id: number; name: string } | null;
        enrollment_status: string;
        turns: Turn[];
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: props.course.title, href: courseShow(props.course.id).url },
    { title: 'Percakapan', href: index(props.course.id).url },
    { title: props.conversation.learner.name, href: '#' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Percakapan · ${conversation.learner.name}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                eyebrow="Tutor"
                :title="conversation.learner.name"
                :description="conversation.lesson.title"
                :back-href="index(course.id).url"
                back-label="Kembali ke Percakapan"
            >
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge v-if="conversation.offering" variant="outline">
                            {{ conversation.offering.name }}
                        </Badge>
                        <Badge variant="secondary">
                            {{ enrollmentStatusLabel(conversation.enrollment_status) }}
                        </Badge>
                    </div>
                </template>
            </PageHeader>

            <!--
                The two voices are shaped differently on purpose: the Learner's
                turn is a quiet bubble, the Tutor's is a rule and prose. A
                lecturer answering in full does not fit in a balloon.
            -->
            <div class="mx-auto w-full max-w-[var(--tutor-measure)] space-y-6">
                <article
                    v-for="turn in conversation.turns"
                    :key="turn.id"
                    :class="
                        turn.role === 'learner'
                            ? 'rounded-lg bg-surface-2 p-4'
                            : 'border-l-2 border-primary pl-4'
                    "
                >
                    <header class="flex items-baseline justify-between gap-4">
                        <p
                            class="text-xs font-medium"
                            :class="turn.role === 'learner' ? 'text-subtle-foreground' : 'text-primary'"
                        >
                            {{ turn.role === 'learner' ? conversation.learner.name : 'Tutor' }}
                        </p>
                        <p v-if="turn.created_at" class="text-xs text-muted-foreground">
                            {{ formatDateTime(turn.created_at) }}
                        </p>
                    </header>
                    <p class="mt-2 whitespace-pre-line text-sm text-foreground">{{ turn.body }}</p>
                </article>

                <p v-if="conversation.turns.length === 0" class="text-sm text-muted-foreground">
                    Percakapan ini belum berisi turn.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
