<script setup lang="ts">
// =============================================================================
// Learner Dashboard Page
// Uses FeaturedCoursesCarousel, MyLearningCard, CourseInvitationCard, BrowseCourseCard
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Mail } from 'lucide-vue-next';
import { computed } from 'vue';
import MyLearningCard from '@/components/courses/MyLearningCard.vue';
import FeaturedCoursesCarousel from '@/components/courses/FeaturedCoursesCarousel.vue';
import CourseInvitationCard from '@/components/courses/CourseInvitationCard.vue';
import BrowseCourseCard from '@/components/courses/BrowseCourseCard.vue';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';
import MyLearningController from '@/actions/App/Http/Controllers/MyLearningController';
import type { DifficultyLevel } from '@/types';

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
const appName = computed(() => page.props.name || 'E-Learning');

// Map CourseItem to FeaturedCourse format
const featuredCoursesFormatted = computed(() =>
    props.featuredCourses.map(course => ({
        id: course.id,
        title: course.title,
        short_description: course.short_description,
        thumbnail_path: course.thumbnail_path,
        duration: (course.duration ?? course.estimated_duration_minutes ?? 0) as number,
        instructor: (course.instructor ?? course.user?.name ?? '') as string,
        category: typeof course.category === 'string' ? course.category : course.category?.name ?? null,
        enrollments_count: course.enrollments_count,
    }))
);

// Map CourseItem to BrowseCourse format
const browseCoursesFormatted = computed(() =>
    props.browseCourses.map(course => ({
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
    }))
);
</script>

<template>
    <Head title="Dashboard Pembelajaran" />

    <div class="min-h-screen bg-background">
        <Navbar :app-name="appName" />

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessages />

            <div class="flex flex-col gap-8">
                <!-- Featured Courses Carousel -->
                <FeaturedCoursesCarousel :courses="featuredCoursesFormatted" />

                <!-- My Learning Section -->
                <section v-if="myLearning.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <BookOpen class="h-5 w-5" />
                            Pembelajaran Saya
                        </h2>
                        <Link :href="MyLearningController().url" class="text-sm text-primary hover:underline">
                            Lihat Semua
                        </Link>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <MyLearningCard
                            v-for="item in myLearning"
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
                </section>

                <!-- Invited Courses Section -->
                <section v-if="invitedCourses.length > 0">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xl font-bold">
                            <Mail class="h-5 w-5 text-primary" />
                            Undangan Kursus
                            <Badge variant="secondary" class="ml-2">
                                {{ invitedCourses.length }}
                            </Badge>
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <CourseInvitationCard
                            v-for="item in invitedCourses"
                            :key="item.id"
                            :invitation="item"
                        />
                    </div>
                </section>

                <!-- Browse Courses Section -->
                <section v-if="browseCourses.length > 0">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold">Jelajahi Kursus</h2>
                        <Link :href="coursesIndex().url" class="text-sm text-primary hover:underline">
                            Lihat Semua
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

                <!-- Empty State -->
                <div
                    v-if="featuredCourses.length === 0 && myLearning.length === 0 && invitedCourses.length === 0 && browseCourses.length === 0"
                    class="flex flex-1 flex-col items-center justify-center py-12 text-center"
                >
                    <BookOpen class="h-16 w-16 text-muted-foreground mb-4" />
                    <h2 class="text-xl font-semibold mb-2">Belum Ada Kursus</h2>
                    <p class="text-muted-foreground mb-4">
                        Belum ada kursus yang tersedia saat ini.
                    </p>
                </div>
            </div>
        </main>

        <Footer :app-name="appName" />
    </div>
</template>
