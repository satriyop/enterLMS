<script setup lang="ts">
// =============================================================================
// Learner Dashboard — Claude B guided: next-action hero + AppLayout shell
// =============================================================================

import EmptyState from '@/components/crud/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Award, BookOpen, Mail, Play, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import MyLearningCard from '@/components/courses/MyLearningCard.vue';
import FeaturedCoursesCarousel from '@/components/courses/FeaturedCoursesCarousel.vue';
import CourseInvitationCard from '@/components/courses/CourseInvitationCard.vue';
import BrowseCourseCard from '@/components/courses/BrowseCourseCard.vue';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, DifficultyLevel } from '@/types';

// =============================================================================
// Types
// =============================================================================

interface UserSummary {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
}

interface CourseItem {
    id: number;
    course_id?: number;
    title: string;
    slug: string;
    short_description: string;
    thumbnail_path: string | null;
    difficulty_level: DifficultyLevel;
    duration?: number;
    estimated_duration_minutes?: number;
    manual_duration_minutes?: number | null;
    instructor?: string;
    user?: UserSummary;
    category?: string | Category | null;
    enrollments_count?: number;
    progress_percentage?: number;
    enrolled_at?: string;
    last_lesson_id?: number | null;
    lessons_count?: number;
    status?: string;
}

interface InvitedCourse {
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
    lessons_count: number;
    invited_by: string;
    message: string | null;
    invited_at: string;
    expires_at: string | null;
}

interface Props {
    featuredCourses: CourseItem[];
    myLearning: CourseItem[];
    invitedCourses: InvitedCourse[];
    browseCourses: CourseItem[];
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const page = usePage();
const userName = computed(() => page.props.auth.user?.name ?? 'Peserta');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/learner/dashboard' },
];

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) {
        return 'Selamat pagi';
    }
    if (hour < 15) {
        return 'Selamat siang';
    }
    if (hour < 18) {
        return 'Selamat sore';
    }
    return 'Selamat malam';
});

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

/** Prefer active incomplete; fall back to any incomplete; ordered by controller (recent first). */
const nextAction = computed(() => {
    const incomplete = props.myLearning.filter(
        (item) => (item.progress_percentage ?? 0) < 100 && item.status !== 'completed',
    );
    const active = incomplete.find((item) => item.status === 'active');
    return active ?? incomplete[0] ?? null;
});

const nextActionCourseId = computed(() => {
    if (!nextAction.value) {
        return null;
    }
    return nextAction.value.course_id ?? nextAction.value.id;
});

const nextActionHref = computed(() => {
    if (!nextAction.value || !nextActionCourseId.value) {
        return null;
    }
    const lessonId = nextAction.value.last_lesson_id;
    if (lessonId) {
        return `/courses/${nextActionCourseId.value}/lessons/${lessonId}`;
    }
    return `/courses/${nextActionCourseId.value}`;
});

const nextActionProgress = computed(() => nextAction.value?.progress_percentage ?? 0);

const nextActionCtaLabel = computed(() =>
    nextActionProgress.value > 0 ? 'Lanjutkan belajar' : 'Mulai belajar',
);

const activeLearningCount = computed(
    () =>
        props.myLearning.filter(
            (item) => item.status === 'active' || ((item.progress_percentage ?? 0) < 100 && item.status !== 'completed'),
        ).length,
);

const completedLearningCount = computed(
    () =>
        props.myLearning.filter(
            (item) => item.status === 'completed' || (item.progress_percentage ?? 0) >= 100,
        ).length,
);

const featuredCoursesFormatted = computed(() =>
    props.featuredCourses.map((course) => ({
        id: course.id,
        title: course.title,
        short_description: course.short_description,
        thumbnail_path: course.thumbnail_path,
        duration: (course.duration ?? course.estimated_duration_minutes ?? 0) as number,
        instructor: (course.instructor ?? course.user?.name ?? '') as string,
        category: typeof course.category === 'string' ? course.category : course.category?.name ?? null,
        enrollments_count: course.enrollments_count,
    })),
);

const browseCoursesFormatted = computed(() =>
    props.browseCourses.map((course) => ({
        id: course.id,
        title: course.title,
        slug: course.slug,
        short_description: course.short_description,
        thumbnail_path: course.thumbnail_path,
        difficulty_level: course.difficulty_level,
        estimated_duration_minutes: course.estimated_duration_minutes ?? 0,
        manual_duration_minutes: course.manual_duration_minutes ?? null,
        user: course.user ?? { id: 0, name: '' },
        category: typeof course.category === 'string' ? { id: 0, name: course.category } : course.category ?? null,
        lessons_count: course.lessons_count ?? 0,
        enrollments_count: course.enrollments_count ?? 0,
    })),
);

const hasCatalogContent = computed(
    () =>
        props.featuredCourses.length > 0
        || props.browseCourses.length > 0
        || props.invitedCourses.length > 0,
);

const isPlatformEmpty = computed(() => props.myLearning.length === 0 && !hasCatalogContent.value);

/** Other in-progress courses (exclude the next-action hero course). */
const otherLearning = computed(() => {
    if (!nextAction.value) {
        return props.myLearning;
    }
    return props.myLearning.filter((item) => item.id !== nextAction.value!.id);
});
</script>

<template>
    <Head title="Dashboard Pembelajaran" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-10 overflow-x-auto px-4 py-8 md:px-8 md:py-10">
            <!-- Page head — Claude A editorial type + Claude B guidance copy -->
            <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl space-y-3">
                    <p class="text-eyebrow">{{ todayLabel }}</p>
                    <h1 class="text-editorial-h1 text-foreground">
                        {{ greeting }}, {{ userName }}.
                    </h1>
                    <p class="text-lead">
                        <template v-if="nextAction">
                            Satu hal yang paling mendesak ada di kartu
                            <span class="text-foreground">Langkah berikutnya</span>
                            di bawah.
                        </template>
                        <template v-else-if="myLearning.length === 0 && hasCatalogContent">
                            Pilih kursus di katalog untuk mulai belajar.
                        </template>
                        <template v-else-if="isPlatformEmpty">
                            Katalog masih kosong — hubungi pengelola pembelajaran bila Anda membutuhkan modul wajib.
                        </template>
                        <template v-else>
                            Halaman ini merangkum progres pembelajaran Anda.
                        </template>
                    </p>
                </div>
                <Button as-child variant="outline" class="shrink-0">
                    <Link :href="coursesIndex().url">Jelajahi kursus</Link>
                </Button>
            </div>

            <!-- Next-action: B hierarchy, A calm surface (hairline, soft pine, no heavy border) -->
            <section
                v-if="nextAction && nextActionHref"
                class="overflow-hidden rounded-[10px] border border-border bg-card"
                aria-labelledby="next-action-heading"
            >
                <div class="flex flex-wrap items-center gap-3 border-b border-border bg-primary-soft/70 px-5 py-3.5 sm:px-7">
                    <span
                        class="inline-flex items-center rounded-full bg-primary px-2.5 py-0.5 text-[0.7rem] font-semibold tracking-wide text-primary-foreground uppercase"
                    >
                        Langkah berikutnya
                    </span>
                    <span class="text-sm text-muted-foreground">
                        Satu tindakan yang paling mendesak
                    </span>
                </div>
                <div class="flex flex-col gap-6 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-7">
                    <div class="flex min-w-0 items-center gap-5">
                        <div
                            class="relative flex h-[4.5rem] w-[4.5rem] shrink-0 items-center justify-center rounded-full"
                            :style="{
                                background: `conic-gradient(var(--primary) ${nextActionProgress}%, var(--border) 0)`,
                            }"
                            role="img"
                            :aria-label="`Progres ${nextActionProgress} persen`"
                        >
                            <div class="absolute inset-[5px] flex items-center justify-center rounded-full bg-card">
                                <span class="text-sm font-semibold tabular-nums tracking-tight text-foreground">
                                    {{ nextActionProgress }}%
                                </span>
                            </div>
                        </div>
                        <div class="min-w-0 space-y-1.5">
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-if="nextAction.status === 'active'"
                                    variant="secondary"
                                    class="font-normal"
                                >
                                    Sedang dipelajari
                                </Badge>
                                <Badge
                                    v-if="nextAction.category"
                                    variant="outline"
                                    class="font-normal"
                                >
                                    {{ nextAction.category }}
                                </Badge>
                            </div>
                            <h2
                                id="next-action-heading"
                                class="font-display truncate text-xl font-normal tracking-tight text-foreground sm:text-2xl"
                            >
                                {{ nextAction.title }}
                            </h2>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                <template v-if="nextAction.last_lesson_id">
                                    Lanjut dari pelajaran terakhir · {{ nextActionProgress }}% selesai
                                </template>
                                <template v-else-if="nextActionProgress === 0">
                                    Belum ada progres · buka kursus untuk memulai
                                </template>
                                <template v-else>
                                    {{ nextActionProgress }}% selesai · lanjutkan pembelajaran
                                </template>
                            </p>
                        </div>
                    </div>
                    <Button as-child size="lg" class="w-full shrink-0 sm:w-auto">
                        <Link :href="nextActionHref">
                            <Play class="mr-1.5 h-4 w-4" />
                            {{ nextActionCtaLabel }}
                        </Link>
                    </Button>
                </div>
            </section>

            <!-- Summary stats — quiet hairline cards -->
            <section aria-label="Ringkasan Anda">
                <div class="section-hairline">
                    <h2 class="text-editorial-h2">Ringkasan Anda</h2>
                    <p class="text-tiny">
                        Diperbarui saat pelajaran atau kuis selesai
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link :href="MyLearningController().url">
                        <Card class="h-full gap-0 py-0 transition-colors hover:border-[color:var(--border)] hover:bg-secondary/40">
                            <CardContent class="flex items-center gap-3.5 p-5">
                                <div class="rounded-[8px] bg-primary-soft p-2.5">
                                    <BookOpen class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-stat">
                                        {{ activeLearningCount }}
                                    </p>
                                    <p class="text-tiny">Kursus berjalan</p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link :href="MyLearningController().url + '?status=completed'">
                        <Card class="h-full gap-0 py-0 transition-colors hover:bg-secondary/40">
                            <CardContent class="flex items-center gap-3.5 p-5">
                                <div class="rounded-[8px] bg-primary-soft p-2.5">
                                    <BookOpen class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-stat">
                                        {{ completedLearningCount }}
                                    </p>
                                    <p class="text-tiny">Kursus selesai</p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link href="/certificates">
                        <Card class="h-full gap-0 py-0 transition-colors hover:bg-secondary/40">
                            <CardContent class="flex items-center gap-3.5 p-5">
                                <div class="rounded-[8px] bg-gold-soft p-2.5">
                                    <Award class="h-5 w-5 text-gold" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-foreground">Sertifikat</p>
                                    <p class="text-sm text-muted-foreground">Lihat sertifikat Anda</p>
                                </div>
                                <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                            </CardContent>
                        </Card>
                    </Link>
                    <Link
                        v-if="invitedCourses.length > 0"
                        href="#undangan"
                    >
                        <Card class="h-full gap-0 py-0 transition-colors hover:bg-secondary/40">
                            <CardContent class="flex items-center gap-3.5 p-5">
                                <div class="rounded-[8px] bg-primary-soft p-2.5">
                                    <Mail class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-stat">
                                        {{ invitedCourses.length }}
                                    </p>
                                    <p class="text-tiny">Undangan menunggu</p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                    <Link
                        v-else
                        :href="coursesIndex().url"
                    >
                        <Card class="h-full gap-0 py-0 transition-colors hover:bg-secondary/40">
                            <CardContent class="flex items-center gap-3.5 p-5">
                                <div class="rounded-[8px] bg-primary-soft p-2.5">
                                    <Mail class="h-5 w-5 text-primary" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium">Jelajahi</p>
                                    <p class="text-sm text-muted-foreground">Temukan kursus baru</p>
                                </div>
                                <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>

            <!-- Featured -->
            <FeaturedCoursesCarousel :courses="featuredCoursesFormatted" />

            <!-- My Learning -->
            <section>
                <div class="section-hairline">
                    <h2 class="text-editorial-h2">
                        {{ nextAction ? 'Lanjutkan kursus lain' : 'Pembelajaran Saya' }}
                    </h2>
                    <Link
                        v-if="myLearning.length > 0"
                        :href="MyLearningController().url"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        Lihat semua →
                    </Link>
                </div>

                <div
                    v-if="otherLearning.length > 0"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <MyLearningCard
                        v-for="item in otherLearning"
                        :key="item.id"
                        :course="{
                            id: item.course_id ?? item.id,
                            title: item.title,
                            slug: item.slug,
                            thumbnail_path: item.thumbnail_path,
                            instructor: item.instructor ?? '',
                            progress_percentage: item.progress_percentage,
                            last_lesson_id: item.last_lesson_id,
                            duration: item.duration ?? 0,
                            difficulty_level: item.difficulty_level,
                            lessons_count: item.lessons_count,
                        }"
                    />
                </div>

                <EmptyState
                    v-else-if="myLearning.length === 0 && hasCatalogContent"
                    :icon="BookOpen"
                    title="Belum ada kursus yang diikuti"
                    description="Anda belum mendaftar ke kursus apa pun. Pilih kursus di katalog — progres dan sertifikat akan muncul di sini setelah Anda mulai belajar."
                >
                    <template #action>
                        <Button as-child>
                            <Link :href="coursesIndex().url">Jelajahi kursus untuk mulai</Link>
                        </Button>
                    </template>
                </EmptyState>

                <p
                    v-else-if="myLearning.length > 0 && otherLearning.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Hanya satu kursus aktif — fokus pada langkah berikutnya di atas.
                </p>
            </section>

            <!-- Invited -->
            <section v-if="invitedCourses.length > 0" id="undangan">
                <div class="section-hairline">
                    <h2 class="text-editorial-h2">
                        Undangan kursus
                    </h2>
                    <Badge variant="secondary">{{ invitedCourses.length }}</Badge>
                </div>
                <div class="space-y-4">
                    <CourseInvitationCard
                        v-for="item in invitedCourses"
                        :key="item.id"
                        :invitation="item"
                    />
                </div>
            </section>

            <!-- Browse -->
            <section v-if="browseCourses.length > 0">
                <div class="section-hairline">
                    <h2 class="text-editorial-h2">Jelajahi kursus</h2>
                    <Link :href="coursesIndex().url" class="text-sm font-medium text-primary hover:underline">
                        Lihat semua →
                    </Link>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <BrowseCourseCard
                        v-for="course in browseCoursesFormatted"
                        :key="course.id"
                        :course="course"
                    />
                </div>
            </section>

            <!-- Platform empty -->
            <EmptyState
                v-if="isPlatformEmpty"
                :icon="BookOpen"
                title="Katalog kursus masih kosong"
                description="Belum ada kursus yang dipublikasikan di platform. Hubungi content manager atau admin agar modul wajib unit Anda dapat ditugaskan."
            />
        </div>
    </AppLayout>
</template>
