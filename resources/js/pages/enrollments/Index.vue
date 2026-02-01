<script setup lang="ts">
// =============================================================================
// My Learning Page - Shows all enrollments with filtering by status
// Uses MyLearningCard component, status filter tabs, pagination
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import MyLearningCard from '@/components/courses/MyLearningCard.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { BookOpen, GraduationCap } from 'lucide-vue-next';
import { computed } from 'vue';
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';
import type { DifficultyLevel, PaginationLink } from '@/types';

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

const page = usePage();
const appName = computed(() => page.props.name || 'E-Learning');

// Status filter configuration
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

// =============================================================================
// Filter Actions
// =============================================================================

const filterByStatus = (status: string | null) => {
    router.get(
        MyLearningController().url,
        status ? { status } : {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <Head title="Pembelajaran Saya" />

    <div class="min-h-screen bg-background">
        <Navbar :app-name="appName" />

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessages />

            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="flex items-center gap-3 text-2xl font-bold sm:text-3xl">
                    <GraduationCap class="h-8 w-8 text-primary" />
                    Pembelajaran Saya
                    <Badge variant="secondary" class="text-base">
                        {{ enrollments.total }}
                    </Badge>
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Kelola dan lanjutkan pembelajaran kursus Anda
                </p>
            </div>

            <!-- Status Filter Tabs -->
            <div class="mb-6 border-b">
                <nav class="-mb-px flex gap-4 overflow-x-auto">
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.key || 'all'"
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
                            id: item.id,
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

                <!-- Pagination -->
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

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-16 text-center"
            >
                <BookOpen class="mb-4 h-16 w-16 text-muted-foreground" />
                <h2 class="mb-2 text-xl font-semibold">Belum Ada Kursus</h2>
                <p class="mb-6 text-muted-foreground">
                    {{
                        currentStatus
                            ? 'Tidak ada kursus dengan status ini'
                            : 'Mulai perjalanan belajar Anda dengan mendaftar kursus'
                    }}
                </p>
                <Link :href="coursesIndex().url">
                    <button
                        class="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Jelajahi Kursus
                    </button>
                </Link>
            </div>
        </main>

        <Footer :app-name="appName" />
    </div>
</template>
