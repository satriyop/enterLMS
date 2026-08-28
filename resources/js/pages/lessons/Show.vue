<script setup lang="ts">
// =============================================================================
// Lesson Show Page
// Displays lesson content with progress tracking and course navigation
// =============================================================================

import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import LessonHeader from '@/components/lesson/LessonHeader.vue';
import LessonNavigationBar from '@/components/lesson/LessonNavigationBar.vue';
import LessonContentRouter from '@/components/lesson/LessonContentRouter.vue';
import LessonOverviewTab from '@/components/lesson/LessonOverviewTab.vue';
import LessonTabNav from '@/components/lesson/LessonTabNav.vue';
import LessonTutorPanel from '@/components/lesson/LessonTutorPanel.vue';
import CourseSidebar from '@/components/courses/CourseSidebar.vue';
import { useLessonProgress } from '@/composables/useLessonProgress';
import type {
    Category,
    ContentType,
    EnrollmentStatus,
    LessonProgress,
    Media,
    UserSummary,
} from '@/types';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface ViewableLesson {
    id: number;
    title: string;
    description: string | null;
    content_type: ContentType;
    rich_content: Record<string, unknown> | null;
    rich_content_html: string | null;
    youtube_url: string | null;
    youtube_video_id: string | null;
    estimated_duration_minutes: number | null;
    is_free_preview: boolean;
    media: Media[];
}

interface SidebarLesson {
    id: number;
    title: string;
    content_type: ContentType;
    is_free_preview: boolean;
    order: number;
    estimated_duration_minutes: number | null;
    is_completed?: boolean;
}

interface SidebarSection {
    id: number;
    title: string;
    order: number;
    lessons: SidebarLesson[];
}

interface LessonCourse {
    id: number;
    title: string;
    slug: string;
    user: UserSummary;
    category: Category | null;
    sections: SidebarSection[];
}

interface LessonEnrollment {
    id: number;
    status: EnrollmentStatus;
    progress_percentage: number;
}

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

interface NavigationLesson {
    id: number;
    title: string;
    section_title: string;
    is_completed?: boolean;
}

interface Props {
    course: LessonCourse;
    lesson: ViewableLesson;
    enrollment: LessonEnrollment | null;
    conversation: LessonConversation | null;
    lessonProgress: LessonProgress | null;
    prevLesson: NavigationLesson | null;
    nextLesson: NavigationLesson | null;
    allLessons: NavigationLesson[];
}

const props = defineProps<Props>();

// =============================================================================
// Progress Tracking (Composable)
// =============================================================================

const {
    isLessonCompleted,
    courseProgressPercentage,
    handlePageChange,
    handlePaginationReady,
    handleDocumentLoaded,
    handleMediaTimeUpdate,
    handleMediaPause,
} = useLessonProgress({
    courseId: props.course.id,
    lessonId: props.lesson.id,
    enrollmentId: props.enrollment?.id ?? null,
    initialProgress: props.lessonProgress,
    initialCourseProgress: props.enrollment?.progress_percentage ?? 0,
});

// =============================================================================
// UI State
// =============================================================================

const sidebarOpen = ref(true);
const activeTab = ref<'overview' | 'notes'>('overview');

const tabs = [
    { id: 'overview' as const, label: 'Ikhtisar' },
    { id: 'notes' as const, label: 'Catatan' },
];

// =============================================================================
// Computed Properties
// =============================================================================

const currentLessonIndex = computed(() => {
    return props.allLessons.findIndex(l => l.id === props.lesson.id) + 1;
});

const lessonCompletionMap = computed(() => {
    const map: Record<number, boolean> = {};
    props.allLessons.forEach(lesson => {
        map[lesson.id] = lesson.is_completed ?? false;
    });
    map[props.lesson.id] = isLessonCompleted.value;
    return map;
});

// =============================================================================
// Lifecycle
// =============================================================================

onMounted(() => {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
});
</script>

<template>
    <Head :title="lesson.title" />

    <div class="h-screen flex flex-col">
        <LessonHeader
            :course-id="course.id"
            :course-title="course.title"
            :is-enrolled="!!enrollment"
            :progress-percentage="courseProgressPercentage"
            :current-lesson-index="currentLessonIndex"
            :total-lessons="allLessons.length"
            :sidebar-open="sidebarOpen"
            @toggle-sidebar="sidebarOpen = !sidebarOpen"
        />

        <div class="flex min-h-0 flex-1 overflow-hidden">
            <main class="relative flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                <div class="relative min-h-0 flex-1">
                    <LessonContentRouter
                        class="h-full"
                        :content-type="lesson.content_type"
                        :course-id="course.id"
                        :lesson-id="lesson.id"
                        :rich-content-html="lesson.rich_content_html"
                        :youtube-video-id="lesson.youtube_video_id"
                        :media="lesson.media"
                        :lesson-progress="lessonProgress"
                        @page-change="handlePageChange"
                        @pagination-ready="handlePaginationReady"
                        @document-loaded="handleDocumentLoaded"
                        @media-time-update="handleMediaTimeUpdate"
                        @media-pause="handleMediaPause"
                    />

                    <LessonTutorPanel
                        v-if="conversation"
                        :course-id="course.id"
                        :lesson-id="lesson.id"
                        :conversation="conversation"
                    />
                </div>

                <div class="shrink-0 border-t border-border">
                    <LessonNavigationBar
                        :course-id="course.id"
                        :lesson-title="lesson.title"
                        :prev-lesson="prevLesson"
                        :next-lesson="nextLesson"
                    />

                    <LessonTabNav v-model="activeTab" :tabs="tabs" />

                    <div class="max-h-32 overflow-auto p-4">
                        <LessonOverviewTab
                            v-if="activeTab === 'overview'"
                            :description="lesson.description"
                            :estimated-duration-minutes="lesson.estimated_duration_minutes"
                            :content-type="lesson.content_type"
                        />

                        <div v-if="activeTab === 'notes'" class="py-2 text-center">
                            <p class="text-muted-foreground">Fitur catatan akan segera hadir.</p>
                        </div>
                    </div>
                </div>
            </main>

            <CourseSidebar
                :course-id="course.id"
                :sections="course.sections"
                :current-lesson-id="lesson.id"
                :enrollment="enrollment"
                :completion-map="lessonCompletionMap"
                :visible="sidebarOpen"
                @close="sidebarOpen = false"
            />
        </div>
    </div>
</template>
