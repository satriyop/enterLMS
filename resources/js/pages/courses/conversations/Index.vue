<script setup lang="ts">
// =============================================================================
// Conversation Review
// What the Tutor taught, for the LMS Admin who runs the Course and the
// Facilitator of an Offering on it. CONTEXT.md grants both this read.
// =============================================================================

import { index, show } from '@/actions/App/Http/Controllers/CourseConversationController';
import { show as courseShow } from '@/actions/App/Http/Controllers/CourseController';
import EmptyState from '@/components/crud/EmptyState.vue';
import PageHeader from '@/components/crud/PageHeader.vue';
import Pagination from '@/components/crud/Pagination.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { useSearch } from '@/composables/ui/useSearch';
import { formatRelativeTime } from '@/lib/date';
import { enrollmentStatusLabel } from '@/lib/formatters';
import type { BreadcrumbItem, PaginationLink } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { MessagesSquare } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ConversationRow {
    id: number;
    turns_count: number;
    last_turn_at: string | null;
    opening_question: string | null;
    lesson: { id: number; title: string };
    learner: { id: number; name: string; email: string };
    offering: { id: number; name: string } | null;
    enrollment_status: string;
}

interface Props {
    course: { id: number; title: string };
    conversations: {
        data: ConversationRow[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    lessons: { id: number; title: string }[];
    offerings: { id: number; name: string }[];
    filters: {
        search?: string;
        lesson_id?: number | null;
        offering_id?: number | null;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: props.course.title, href: courseShow(props.course.id).url },
    { title: 'Percakapan', href: index(props.course.id).url },
];

// `all` rather than an empty string: a Select cannot hold '' as a value, and a
// filter that silently means "no filter" is easier to read spelled out.
const lessonId = ref(props.filters.lesson_id ? String(props.filters.lesson_id) : 'all');
const offeringId = ref(props.filters.offering_id ? String(props.filters.offering_id) : 'all');

const asParam = (value: string) => (value === 'all' ? undefined : value);

const { query: search } = useSearch({
    url: () => index(props.course.id).url,
    initial: props.filters.search ?? '',
    extraParams: () => ({
        lesson_id: asParam(lessonId.value),
        offering_id: asParam(offeringId.value),
    }),
});

watch([lessonId, offeringId], () => {
    router.get(
        index(props.course.id).url,
        {
            search: search.value || undefined,
            lesson_id: asParam(lessonId.value),
            offering_id: asParam(offeringId.value),
        },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Percakapan · ${course.title}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                eyebrow="Tutor"
                title="Percakapan"
                :description="`Apa yang ditanyakan Learner dan apa yang diajarkan Tutor pada ${course.title}.`"
                :back-href="courseShow(course.id).url"
                back-label="Kembali ke Course"
            />

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <Select v-model="lessonId">
                        <SelectTrigger class="w-full sm:w-64">
                            <SelectValue placeholder="Semua Lesson" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Lesson</SelectItem>
                            <SelectItem
                                v-for="lesson in lessons"
                                :key="lesson.id"
                                :value="String(lesson.id)"
                            >
                                {{ lesson.title }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-if="offerings.length > 1" v-model="offeringId">
                        <SelectTrigger class="w-full sm:w-56">
                            <SelectValue placeholder="Semua Kelas" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Kelas</SelectItem>
                            <SelectItem
                                v-for="offering in offerings"
                                :key="offering.id"
                                :value="String(offering.id)"
                            >
                                {{ offering.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="w-full lg:w-80">
                    <SearchInput v-model="search" placeholder="Cari nama atau email Learner..." />
                </div>
            </div>

            <EmptyState
                v-if="conversations.data.length === 0"
                :icon="MessagesSquare"
                title="Belum ada percakapan"
                description="Percakapan muncul di sini setelah seorang Learner bertanya pada Tutor tentang sebuah Lesson."
            />

            <template v-else>
                <div class="rounded-xl border border-border bg-surface">
                    <ul class="divide-y divide-border">
                        <li v-for="row in conversations.data" :key="row.id">
                            <Link
                                :href="show([course.id, row.id]).url"
                                class="flex flex-col gap-2 px-5 py-4 transition-colors hover:bg-surface-2/50 sm:flex-row sm:items-start sm:justify-between sm:gap-6"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-foreground">{{ row.learner.name }}</span>
                                        <span class="text-sm text-muted-foreground">{{ row.lesson.title }}</span>
                                        <Badge v-if="row.offering" variant="outline">{{ row.offering.name }}</Badge>
                                        <Badge v-if="row.enrollment_status !== 'active'" variant="secondary">
                                            {{ enrollmentStatusLabel(row.enrollment_status) }}
                                        </Badge>
                                    </div>
                                    <p v-if="row.opening_question" class="mt-1 truncate text-sm text-muted-foreground">
                                        &ldquo;{{ row.opening_question }}&rdquo;
                                    </p>
                                </div>
                                <div class="shrink-0 text-sm text-muted-foreground sm:text-right">
                                    <div class="tabular-nums">{{ row.turns_count }} turn</div>
                                    <div v-if="row.last_turn_at">{{ formatRelativeTime(row.last_turn_at) }}</div>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </div>

                <Pagination
                    :links="conversations.links"
                    :current-page="conversations.current_page"
                    :last-page="conversations.last_page"
                    :from="conversations.from"
                    :to="conversations.to"
                    :total="conversations.total"
                />
            </template>
        </div>
    </AppLayout>
</template>
