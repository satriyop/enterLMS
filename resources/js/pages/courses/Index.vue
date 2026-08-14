<script setup lang="ts">
import { index, create, show, edit, destroy, restore, forceDelete } from '@/actions/App/Http/Controllers/CourseController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import DataCard from '@/components/crud/DataCard.vue';
import FilterTabs from '@/components/crud/FilterTabs.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import Pagination from '@/components/crud/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Category,
    type CourseStatus,
    type CourseVisibility,
    type DifficultyLevel,
    type PaginationLink,
} from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { useSearch } from '@/composables/ui/useSearch';
import { useViewMode } from '@/composables/ui/useViewMode';
import { Plus, BookOpen, Clock, Layers, Eye, Pencil, Trash2, RotateCcw, LayoutGrid, List } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { formatDuration, difficultyLabel, courseStatusLabel, statusBadgeVariant } from '@/lib/utils';

// =============================================================================
// Page-Specific Types
// =============================================================================

/** Course item for list display */
interface CourseListItem {
    id: number;
    title: string;
    slug: string;
    short_description: string;
    status: CourseStatus;
    visibility: CourseVisibility;
    difficulty_level: DifficultyLevel;
    estimated_duration_minutes: number;
    thumbnail_path: string | null;
    category: Category | null;
    sections_count: number;
    lessons_count: number;
    created_at: string;
}

interface Props {
    courses: {
        data: CourseListItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        category_id?: string;
        trashed?: string;
    };
    categories: Category[];
}

const props = defineProps<Props>();

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'lms_admin');
const isTrashed = computed(() => status.value === 'trashed');

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Kursus',
        href: index().url,
    },
];

const status = ref(props.filters.trashed ? 'trashed' : (props.filters.status ?? ''));

const { query: search } = useSearch({
    url: () => index().url,
    initial: props.filters.search ?? '',
    extraParams: () => (status.value === 'trashed'
        ? { trashed: '1' }
        : { status: status.value || undefined }
    ),
});

const { viewMode, setMode, containerClasses } = useViewMode({ key: 'courses' });

const confirmation = useConfirmation();

const statusTabs = computed(() => {
    const tabs = [
        { value: '', label: 'Semua', count: undefined },
        { value: 'draft', label: 'Draft' },
        { value: 'published', label: 'Terbit' },
        { value: 'archived', label: 'Arsip' },
    ];

    if (isAdmin.value) {
        tabs.push({ value: 'trashed', label: 'Terhapus', count: undefined });
    }

    return tabs;
});

const statusBadge = (courseStatus: string) => ({
    label: courseStatusLabel(courseStatus),
    variant: statusBadgeVariant(courseStatus),
});

/** Format duration for display in course meta */
const getFormattedDuration = (minutes: number) => formatDuration(minutes, 'long');

const getCourseActions = (course: CourseListItem) => {
    if (isTrashed.value) {
        return [
            { label: 'Pulihkan', icon: RotateCcw, onClick: () => restoreCourse(course) },
            { label: 'Hapus Permanen', icon: Trash2, variant: 'destructive' as const, onClick: () => forceDeleteCourse(course) },
        ];
    }
    return [
        { label: 'Lihat', href: show(course.id).url, icon: Eye },
        { label: 'Edit', href: edit(course.id).url, icon: Pencil },
        { label: 'Hapus', icon: Trash2, variant: 'destructive' as const, onClick: () => deleteCourse(course) },
    ];
};

const getCourseMeta = (course: CourseListItem) => [
    { icon: Layers, label: `${course.sections_count ?? 0} seksi` },
    { icon: BookOpen, label: `${course.lessons_count ?? 0} materi` },
    { icon: Clock, label: getFormattedDuration(course.estimated_duration_minutes) },
];

watch(status, (value) => {
    const params: Record<string, string | undefined> = { search: search.value || undefined };
    if (value === 'trashed') {
        params.trashed = '1';
    } else {
        params.status = value || undefined;
    }
    router.get(index().url, params, { preserveState: true, replace: true });
});

const deleteCourse = async (course: CourseListItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Hapus Kursus',
        message: `Apakah Anda yakin ingin menghapus kursus "${course.title}"?`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(destroy(course.id).url);
    }
};

const restoreCourse = async (course: CourseListItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Pulihkan Kursus',
        message: `Apakah Anda yakin ingin memulihkan kursus "${course.title}"?`,
    });
    if (confirmed) {
        router.post(restore(course.id).url);
    }
};

const forceDeleteCourse = async (course: CourseListItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Hapus Permanen',
        message: `Apakah Anda yakin ingin menghapus kursus "${course.title}" secara permanen? Tindakan ini tidak dapat dibatalkan.`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(forceDelete(course.id).url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Manajemen Kursus" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Kursus Saya"
                description="Kelola dan buat kursus pembelajaran baru"
            >
                <template #actions>
                    <Link :href="create().url">
                        <Button size="lg" class="gap-2">
                            <Plus class="h-5 w-5" />
                            Buat Kursus
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <FilterTabs v-model="status" :tabs="statusTabs" />

                <div class="flex items-center gap-3">
                    <div class="w-full lg:w-80">
                        <SearchInput v-model="search" placeholder="Cari kursus..." />
                    </div>
                    <div class="hidden items-center gap-1 rounded-lg border p-1 sm:flex">
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'grid' ? 'bg-surface-2 text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('grid')"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'list' ? 'bg-surface-2 text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('list')"
                        >
                            <List class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="courses.data.length === 0"
                :icon="isTrashed ? Trash2 : BookOpen"
                :title="isTrashed ? 'Tidak ada kursus di tempat sampah' : 'Belum ada kursus'"
                :description="isTrashed ? 'Kursus yang dihapus akan muncul di sini.' : 'Mulai perjalanan mengajar Anda dengan membuat kursus pertama.'"
                :action-label="isTrashed ? undefined : 'Buat Kursus Baru'"
                :action-href="isTrashed ? undefined : create().url"
            />

            <template v-else>
                <div :class="containerClasses()">
                    <DataCard
                        v-for="course in courses.data"
                        :key="course.id"
                        :title="course.title"
                        :subtitle="course.category?.name"
                        :description="course.short_description"
                        :thumbnail-url="course.thumbnail_path ? `/storage/${course.thumbnail_path}` : undefined"
                        :href="show(course.id).url"
                        :badges="[statusBadge(course.status), { label: difficultyLabel(course.difficulty_level), variant: 'outline' }]"
                        :meta="getCourseMeta(course)"
                        :actions="getCourseActions(course)"
                    />
                </div>

                <Pagination
                    v-if="courses.last_page > 1"
                    :links="courses.links"
                    :current-page="courses.current_page"
                    :last-page="courses.last_page"
                    :from="courses.from"
                    :to="courses.to"
                    :total="courses.total"
                />
            </template>
        </div>

        <ConfirmationDialog
            :open="confirmation.isOpen.value"
            :title="confirmation.title.value"
            :message="confirmation.message.value"
            :confirm-label="confirmation.confirmLabel.value"
            :cancel-label="confirmation.cancelLabel.value"
            :destructive="confirmation.isDestructive.value"
            @confirm="confirmation.handleConfirm"
            @cancel="confirmation.handleCancel"
        />
    </AppLayout>
</template>
