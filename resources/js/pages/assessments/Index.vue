<script setup lang="ts">
import { index, show, edit } from '@/actions/App/Http/Controllers/AssessmentController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import DataCard from '@/components/crud/DataCard.vue';
import FilterTabs from '@/components/crud/FilterTabs.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import Pagination from '@/components/crud/Pagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { assessmentStatusLabel, statusBadgeVariant } from '@/lib/formatters';
import {
    type BreadcrumbItem,
    type PaginatedResponse,
    AssessmentStatus,
    AssessmentVisibility,
} from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useSearch } from '@/composables/ui/useSearch';
import { useViewMode } from '@/composables/ui/useViewMode';
import { FileText, Clock, CheckCircle, Users, Eye, Pencil, LayoutGrid, List, PlayCircle } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

/** Assessment list item with computed counts */
interface AssessmentListItem {
    id: number;
    title: string;
    description: string;
    status: AssessmentStatus;
    visibility: AssessmentVisibility;
    time_limit_minutes: number | null;
    passing_score: number;
    max_attempts: number;
    questions_count: number;
    attempts_count: number;
    created_at: string;
    updated_at: string;
}

/** Minimal course info for breadcrumbs */
interface AssessmentCourse {
    id: number;
    title: string;
}

interface Props {
    course: AssessmentCourse;
    assessments: PaginatedResponse<AssessmentListItem>;
    filters: {
        search?: string;
        status?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Kursus',
        href: `/courses/${props.course.id}`,
    },
    {
        title: 'Penilaian',
        href: index(props.course).url,
    },
];

// =============================================================================
// State
// =============================================================================

const status = ref(props.filters.status ?? '');

const { query: search } = useSearch({
    url: () => index(props.course).url,
    initial: props.filters.search ?? '',
    extraParams: () => ({ status: status.value || undefined }),
});

const { viewMode, setMode, containerClasses } = useViewMode({ key: 'assessments' });

// =============================================================================
// Computed
// =============================================================================

const statusTabs = computed(() => [
    { value: '', label: 'Semua', count: undefined },
    { value: 'published', label: 'Dipublikasikan' },
    { value: 'draft', label: 'Draft' },
    { value: 'archived', label: 'Diarsipkan' },
]);

// =============================================================================
// Helpers
// =============================================================================

const getAssessmentBadge = (assessmentStatus: string) => ({
    label: assessmentStatusLabel(assessmentStatus),
    variant: statusBadgeVariant(assessmentStatus),
});

const getAssessmentMeta = (assessment: AssessmentListItem) => [
    { icon: FileText, label: `${assessment.questions_count} pertanyaan` },
    { icon: Users, label: `${assessment.attempts_count} percobaan` },
    { icon: Clock, label: assessment.time_limit_minutes ? `${assessment.time_limit_minutes} menit` : 'Tidak ada batas waktu' },
    { icon: CheckCircle, label: `Nilai kelulusan: ${assessment.passing_score}%` },
];

const getAssessmentActions = (assessment: AssessmentListItem) => {
    const actions = [
        { label: 'Lihat', href: show(props.course, assessment).url, icon: Eye },
    ];

    if (assessment.status !== 'published') {
        actions.push({ label: 'Edit', href: edit(props.course, assessment).url, icon: Pencil });
    } else {
        actions.push({ label: 'Mulai', href: `/courses/${props.course.id}/assessments/${assessment.id}/start`, icon: PlayCircle });
    }

    return actions;
};

// =============================================================================
// Watchers
// =============================================================================

watch(status, (value) => {
    router.get(index(props.course).url, { search: search.value, status: value }, { preserveState: true, replace: true });
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Penilaian - ${course.title}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                :title="`Penilaian - ${course.title}`"
                description="Kelola semua penilaian untuk kursus ini"
                :back-href="`/courses/${course.id}`"
                back-label="Kembali ke Kursus"
            />

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <FilterTabs v-model="status" :tabs="statusTabs" />

                <div class="flex items-center gap-3">
                    <div class="w-full lg:w-80">
                        <SearchInput v-model="search" placeholder="Cari penilaian..." />
                    </div>
                    <div class="hidden items-center gap-1 rounded-lg border p-1 sm:flex">
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'grid' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('grid')"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'list' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('list')"
                        >
                            <List class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="assessments.data.length === 0"
                :icon="FileText"
                title="Belum ada penilaian"
                description="Mulai membuat penilaian untuk kursus ini."
                action-label="Buat Penilaian Baru"
                :action-href="`/courses/${course.id}/assessments/create`"
            />

            <template v-else>
                <div :class="containerClasses()">
                    <DataCard
                        v-for="assessment in assessments.data"
                        :key="assessment.id"
                        :title="assessment.title"
                        :description="assessment.description || 'Tidak ada deskripsi'"
                        :href="`/courses/${course.id}/assessments/${assessment.id}`"
                        :badges="[getAssessmentBadge(assessment.status)]"
                        :meta="getAssessmentMeta(assessment)"
                        :actions="getAssessmentActions(assessment)"
                    />
                </div>

                <Pagination
                    v-if="assessments.last_page > 1"
                    :links="assessments.links"
                    :current-page="assessments.current_page"
                    :last-page="assessments.last_page"
                    :from="assessments.from"
                    :to="assessments.to"
                    :total="assessments.total"
                />
            </template>
        </div>
    </AppLayout>
</template>