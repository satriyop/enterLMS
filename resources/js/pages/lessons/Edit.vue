<script setup lang="ts">
// =============================================================================
// Lesson Edit Page
// Create or edit lesson content with various content types
// =============================================================================

import { edit as editCourse } from '@/actions/App/Http/Controllers/CourseController';
import { store, update } from '@/actions/App/Http/Controllers/LessonController';
import PageHeader from '@/components/crud/PageHeader.vue';
import FormSection from '@/components/crud/FormSection.vue';
import InputError from '@/components/InputError.vue';
import LessonContentTypeSelector from '@/components/lesson/LessonContentTypeSelector.vue';
import LessonContentEditor from '@/components/lesson/LessonContentEditor.vue';
import LessonSettingsSidebar from '@/components/lesson/LessonSettingsSidebar.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ContentType, type Media, type TutorBody } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { contentTypeLabel } from '@/lib/formatters';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface Course {
    id: number;
    title: string;
}

interface Section {
    id: number;
    title: string;
    course: Course;
}

interface Lesson {
    id: number;
    title: string;
    description: string | null;
    order: number;
    content_type: ContentType;
    rich_content: Record<string, unknown> | null;
    youtube_url: string | null;
    conference_url: string | null;
    conference_type: 'zoom' | 'google_meet' | 'other' | null;
    estimated_duration_minutes: number | null;
    is_free_preview: boolean;
    media?: Media[];
}

interface Props {
    section: Section;
    lesson: Lesson | null;
    tutor_body?: TutorBody | null;
}

// =============================================================================
// Component Setup
// =============================================================================

const props = withDefaults(defineProps<Props>(), {
    tutor_body: null,
});

const isEditMode = computed(() => props.lesson !== null);
const pageTitle = computed(() => isEditMode.value ? `Edit: ${props.lesson?.title}` : 'Tambah Materi Baru');

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: 'Courses', href: '/courses' },
    { title: props.section.course.title, href: editCourse(props.section.course.id).url },
    { title: props.section.title, href: editCourse(props.section.course.id).url },
    { title: isEditMode.value ? 'Edit Materi' : 'Tambah Materi', href: '#' },
]);

// =============================================================================
// Form State
// =============================================================================

const contentType = ref<ContentType>(props.lesson?.content_type ?? 'text');
const richContent = ref<Record<string, unknown> | null>(props.lesson?.rich_content ?? null);
const youtubeUrl = ref<string>(props.lesson?.youtube_url ?? '');
const conferenceUrl = ref<string>(props.lesson?.conference_url ?? '');
const conferenceType = ref<'zoom' | 'google_meet' | 'other'>(props.lesson?.conference_type ?? 'zoom');
const estimatedDurationMinutes = ref<number | null>(props.lesson?.estimated_duration_minutes ?? null);
const isFreePreview = ref<boolean>(props.lesson?.is_free_preview ?? false);

// =============================================================================
// Computed
// =============================================================================

const getMediaByCollection = (collection: string): Media[] => {
    if (!props.lesson?.media) return [];
    return props.lesson.media.filter(m => m.collection_name === collection);
};

const videoMedia = computed(() => getMediaByCollection('video'));
const audioMedia = computed(() => getMediaByCollection('audio'));
const documentMedia = computed(() => getMediaByCollection('document'));

const selectedContentTypeLabel = computed(() => contentTypeLabel(contentType.value));

const formAction = computed(() => {
    if (isEditMode.value && props.lesson) {
        return { action: update(props.lesson.id).url, method: 'post' as const };
    }
    return { action: store(props.section.id).url, method: 'post' as const };
});

// =============================================================================
// Methods
// =============================================================================

const handleMediaUploaded = () => {
    router.reload({ only: ['lesson', 'tutor_body'] });
};

const handleMediaDeleted = () => {
    router.reload({ only: ['lesson', 'tutor_body'] });
};

const handleMediaError = (message: string) => {
    alert(message);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="pageTitle" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                :title="isEditMode ? 'Edit Materi' : 'Tambah Materi Baru'"
                :description="`${section.course.title} / ${section.title}`"
                :back-href="editCourse(section.course.id).url"
                back-label="Kembali ke Kursus"
            />

            <Form
                v-bind="formAction"
                class="grid gap-6 lg:grid-cols-3"
                #default="{ errors, processing }"
            >
                <input v-if="isEditMode" type="hidden" name="_method" value="PATCH" />

                <div class="space-y-6 lg:col-span-2">
                    <!-- Basic Info -->
                    <FormSection title="Informasi Dasar" description="Informasi utama tentang materi pembelajaran">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <Label for="title" class="text-sm font-medium">
                                    Judul Materi <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    name="title"
                                    :value="lesson?.title ?? ''"
                                    placeholder="Contoh: Pengenalan JavaScript"
                                    class="h-11"
                                    required
                                />
                                <InputError :message="errors.title" />
                            </div>

                            <div class="space-y-2">
                                <Label for="description" class="text-sm font-medium">Deskripsi</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows="3"
                                    class="flex w-full rounded-lg border border-input bg-surface px-4 py-3 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Deskripsi singkat tentang materi ini"
                                    :value="lesson?.description ?? ''"
                                />
                                <InputError :message="errors.description" />
                            </div>
                        </div>
                    </FormSection>

                    <!-- Content Type Selector -->
                    <FormSection title="Tipe Konten" description="Pilih jenis konten untuk materi ini">
                        <input type="hidden" name="content_type" :value="contentType" />
                        <LessonContentTypeSelector v-model="contentType" />
                        <InputError :message="errors.content_type" />
                    </FormSection>

                    <!-- Content Editor -->
                    <FormSection :title="`Konten ${selectedContentTypeLabel}`">
                        <input type="hidden" name="rich_content" :value="richContent ? JSON.stringify(richContent) : ''" />
                        <input type="hidden" name="youtube_url" :value="youtubeUrl" />
                        <input type="hidden" name="conference_url" :value="conferenceUrl" />
                        <input type="hidden" name="conference_type" :value="conferenceType" />

                        <LessonContentEditor
                            :content-type="contentType"
                            :lesson-id="lesson?.id ?? null"
                            :rich-content="richContent"
                            :youtube-url="youtubeUrl"
                            :conference-url="conferenceUrl"
                            :conference-type="conferenceType"
                            :existing-video-media="videoMedia"
                            :existing-audio-media="audioMedia"
                            :existing-document-media="documentMedia"
                            :tutor-body="tutor_body"
                            :errors="errors"
                            @update:rich-content="richContent = $event"
                            @update:youtube-url="youtubeUrl = $event"
                            @update:conference-url="conferenceUrl = $event"
                            @update:conference-type="conferenceType = $event as 'zoom' | 'google_meet' | 'other'"
                            @media-uploaded="handleMediaUploaded"
                            @media-deleted="handleMediaDeleted"
                            @media-error="handleMediaError"
                        />
                    </FormSection>
                </div>

                <!-- Sidebar -->
                <input type="hidden" name="estimated_duration_minutes" :value="estimatedDurationMinutes ?? ''" />
                <input type="hidden" name="is_free_preview" :value="isFreePreview ? 1 : 0" />

                <LessonSettingsSidebar
                    :cancel-href="editCourse(section.course.id).url"
                    :estimated-duration-minutes="estimatedDurationMinutes"
                    :is-free-preview="isFreePreview"
                    :is-processing="processing"
                    :errors="errors"
                    @update:estimated-duration-minutes="estimatedDurationMinutes = $event"
                    @update:is-free-preview="isFreePreview = $event"
                />
            </Form>
        </div>
    </AppLayout>
</template>
