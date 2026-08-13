<script setup lang="ts">
// =============================================================================
// My Learning Page — AppLayout shell + guided empty states (Claude B)
// =============================================================================

import MyLearningCard from '@/components/courses/MyLearningCard.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import { computed } from 'vue';
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, DifficultyLevel, PaginationLink } from '@/types';

// =============================================================================
// Types
// =============================================================================

interface EnrollmentItem {
    id: number;
    course_id: number;
    title: string;
    slug: string;
    short_description: string;
    thumbnail_path: string | null;
    difficulty_level: DifficultyLevel;
    duration: number;
    instructor: string;
    category: string | null;
    progress_percentage: number;
    enrolled_at: string;
    last_lesson_id: number | null;
    lessons_count: number;
    status: string;
}

interface Props {
    enrollments: {
        data: EnrollmentItem[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
    filters: {
        status: string | null;
    };
    statusCounts: {
        all: number;
        active: number;
        completed: number;
        dropped: number;
    };
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/learner/dashboard' },
    { title: 'Pembelajaran saya', href: MyLearningController().url },
];

const statusFilters = computed(() => [
    {
        key: null,
        label: 'Semua',
        count: props.statusCounts.all,
    },
    {
        key: 'active',
        label: 'Sedang Dipelajari',
        count: props.statusCounts.active,
    },
    {
        key: 'completed',
        label: 'Selesai',
        count: props.statusCounts.completed,
    },
    {
        key: 'dropped',
        label: 'Dihentikan',
        count: props.statusCounts.dropped,
    },
]);

const currentStatus = computed(() => props.filters.status);

const emptyTitle = computed(() => {
    if (currentStatus.value === 'active') {
        return 'Tidak ada kursus yang sedang dipelajari';
    }
    if (currentStatus.value === 'completed') {
        return 'Belum ada kursus yang diselesaikan';
    }
    if (currentStatus.value === 'dropped') {
        return 'Tidak ada kursus yang dihentikan';
    }
    return 'Belum ada pembelajaran';
});

const emptyDescription = computed(() => {
    if (currentStatus.value === 'active') {
        return 'Filter ini hanya menampilkan kursus aktif. Pilih “Semua” untuk melihat riwayat, atau daftar ke kursus baru di katalog.';
    }
    if (currentStatus.value === 'completed') {
        return 'Setelah Anda menyelesaikan kursus dan memenuhi syarat kelulusan, kursus akan muncul di sini beserta progres 100%.';
    }
    if (currentStatus.value === 'dropped') {
        return 'Tidak ada pendaftaran yang dihentikan. Kembali ke filter “Semua” atau lanjutkan kursus yang masih aktif.';
    }
    return 'Anda belum terdaftar di kursus mana pun. Jelajahi katalog, pilih modul yang relevan, lalu mulai belajar — progres akan tercatat di halaman ini.';
});

const filterByStatus = (status: string | null) => {
    router.get(
        MyLearningController().url,
        status ? { status } : {},
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Pembelajaran Saya" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 px-4 py-8 md:px-8 md:py-10">
            <!-- Page Header — editorial calm -->
            <div class="space-y-3">
                <p class="text-eyebrow">Belajar</p>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-editorial-h1">Pembelajaran Saya</h1>
                    <Badge variant="secondary" class="text-sm font-normal">
                        {{ enrollments.total }}
                    </Badge>
                </div>
                <p class="text-lead max-w-2xl">
                    Kelola dan lanjutkan kursus Anda. Gunakan filter status untuk memfokuskan daftar.
                </p>
            </div>

            <!-- Status Filter Tabs -->
            <div class="border-b">
                <nav class="-mb-px flex gap-4 overflow-x-auto" aria-label="Filter status">
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.key || 'all'"
                        type="button"
                        @click="filterByStatus(filter.key)"
                        :class="[
                            'flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors',
                            currentStatus === filter.key
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-muted-foreground hover:text-foreground',
                        ]"
                    >
                        {{ filter.label }}
                        <Badge
                            :variant="currentStatus === filter.key ? 'default' : 'secondary'"
                            class="min-w-[2rem] justify-center"
                        >
                            {{ filter.count }}
                        </Badge>
                    </button>
                </nav>
            </div>

            <!-- Enrollments Grid -->
            <div v-if="enrollments.data.length > 0" class="space-y-6">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <MyLearningCard
                        v-for="item in enrollments.data"
                        :key="item.id"
                        :course="{
                            id: item.course_id,
                            title: item.title,
                            slug: item.slug,
                            thumbnail_path: item.thumbnail_path,
                            instructor: item.instructor,
                            progress_percentage: item.progress_percentage,
                            last_lesson_id: item.last_lesson_id,
                            duration: item.duration,
                            difficulty_level: item.difficulty_level,
                            lessons_count: item.lessons_count,
                        }"
                    />
                </div>

                <nav
                    v-if="enrollments.last_page > 1"
                    class="flex items-center justify-center gap-2 pt-4"
                    aria-label="Pagination"
                >
                    <Link
                        v-for="(link, index) in enrollments.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : link.url
                                  ? 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                                  : 'cursor-not-allowed opacity-50',
                        ]"
                        :preserve-state="true"
                        :preserve-scroll="true"
                        v-html="link.label"
                    />
                </nav>
            </div>

            <!-- Empty State — cause + recovery (Claude B) -->
            <EmptyState
                v-else
                :icon="BookOpen"
                :title="emptyTitle"
                :description="emptyDescription"
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <Button
                            v-if="currentStatus"
                            variant="outline"
                            @click="filterByStatus(null)"
                        >
                            Lihat semua status
                        </Button>
                        <Button as-child>
                            <Link :href="coursesIndex().url">Jelajahi kursus</Link>
                        </Button>
                    </div>
                </template>
            </EmptyState>
        </div>
    </AppLayout>
</template>
