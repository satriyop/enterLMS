<script setup lang="ts">
// =============================================================================
// CourseInfoSidebar Component
// Sidebar showing course information, tags, and assessment links
// =============================================================================

import { index as conversationsIndex } from '@/actions/App/Http/Controllers/CourseConversationController';
import { index as offeringsIndex } from '@/actions/App/Http/Controllers/CourseOfferingController';
import FormSection from '@/components/crud/FormSection.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useAcademy } from '@/composables/useAcademy';
import { difficultyLabel, formatDuration } from '@/lib/formatters';
import type {
    Category,
    CourseStatus,
    CourseVisibility,
    DifficultyLevel,
    Tag,
    UserSummary,
} from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    Clock,
    Eye,
    EyeOff,
    FileText,
    Globe,
    Layers,
    MessagesSquare,
    Plus,
} from 'lucide-vue-next';
import { computed } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface Props {
    courseId: number;
    thumbnailPath: string | null;
    title: string;
    status: CourseStatus;
    visibility: CourseVisibility;
    difficultyLevel: DifficultyLevel;
    estimatedDurationMinutes: number;
    totalLessons: number;
    category: Category | null;
    user: UserSummary;
    publishedAt: string | null;
    tags: Tag[];
    /** CONTEXT.md grants this read to LMS Admin and to an Offering's Facilitator. */
    canReviewConversations?: boolean;
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();
const { enabled, label } = useAcademy();

// =============================================================================
// Computed
// =============================================================================

const statusConfig = computed(() => {
    switch (props.status) {
        case 'published':
            return { label: 'Terbit', class: 'bg-ok hover:bg-ok' };
        case 'draft':
            return { label: 'Draft', class: '' };
        case 'archived':
            return { label: 'Arsip', class: '' };
        default:
            return { label: props.status, class: '' };
    }
});

const visibilityConfig = computed(() => {
    switch (props.visibility) {
        case 'public':
            return { label: 'Publik', icon: Globe };
        case 'restricted':
            return { label: 'Terbatas', icon: Eye };
        case 'hidden':
            return { label: 'Tersembunyi', icon: EyeOff };
        default:
            return { label: props.visibility, icon: Globe };
    }
});

const formattedPublishedDate = computed(() => {
    if (!props.publishedAt) return null;
    return new Date(props.publishedAt).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
});
</script>

<template>
    <div class="sticky top-4 space-y-6">
        <!-- Thumbnail -->
        <div v-if="thumbnailPath" class="overflow-hidden rounded-xl border">
            <img
                :src="`/storage/${thumbnailPath}`"
                :alt="title"
                class="aspect-video w-full object-cover"
            />
        </div>

        <!-- Course Information -->
        <FormSection title="Informasi">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Status</span>
                    <Badge :class="statusConfig.class">
                        {{ statusConfig.label }}
                    </Badge>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground"
                        >Visibilitas</span
                    >
                    <span class="flex items-center gap-1.5 text-sm">
                        <component
                            :is="visibilityConfig.icon"
                            class="h-4 w-4"
                        />
                        {{ visibilityConfig.label }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Tingkat</span>
                    <Badge variant="outline">
                        {{ difficultyLabel(difficultyLevel) }}
                    </Badge>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground"
                        >Durasi Total</span
                    >
                    <span class="flex items-center gap-1.5 text-sm font-medium">
                        <Clock class="h-4 w-4 text-muted-foreground" />
                        {{ formatDuration(estimatedDurationMinutes, 'long') }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground"
                        >Jumlah Materi</span
                    >
                    <span class="flex items-center gap-1.5 text-sm font-medium">
                        <BookOpen class="h-4 w-4 text-muted-foreground" />
                        {{ totalLessons }} materi
                    </span>
                </div>
                <div v-if="category" class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Kategori</span>
                    <span class="text-sm font-medium">{{ category.name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground"
                        >Instruktur</span
                    >
                    <span class="text-sm font-medium">{{ user.name }}</span>
                </div>
                <div
                    v-if="formattedPublishedDate"
                    class="flex items-center justify-between"
                >
                    <span class="text-sm text-muted-foreground"
                        >Diterbitkan</span
                    >
                    <span class="flex items-center gap-1.5 text-sm">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        {{ formattedPublishedDate }}
                    </span>
                </div>
            </div>
        </FormSection>

        <!-- Tags -->
        <FormSection v-if="tags && tags.length > 0" title="Tag">
            <div class="flex flex-wrap gap-2">
                <Badge
                    v-for="tag in tags"
                    :key="tag.id"
                    variant="secondary"
                    class="rounded-full"
                >
                    {{ tag.name }}
                </Badge>
            </div>
        </FormSection>

        <FormSection v-if="enabled('offerings')" :title="label('offering')">
            <Link :href="offeringsIndex.url(courseId)" class="block w-full">
                <Button variant="outline" class="w-full justify-start gap-2">
                    <Layers class="h-4 w-4" />
                    <span>Kelola {{ label('offering') }}</span>
                </Button>
            </Link>
        </FormSection>

        <FormSection v-if="canReviewConversations" title="Tutor">
            <Link :href="conversationsIndex.url(courseId)" class="block w-full">
                <Button variant="outline" class="w-full justify-start gap-2">
                    <MessagesSquare class="h-4 w-4" />
                    <span>Percakapan</span>
                </Button>
            </Link>
        </FormSection>

        <!-- Assessment Links -->
        <FormSection title="Penilaian">
            <div class="space-y-3">
                <Link
                    :href="`/courses/${courseId}/assessments`"
                    class="block w-full"
                >
                    <Button
                        variant="outline"
                        class="w-full justify-start gap-2"
                    >
                        <FileText class="h-4 w-4" />
                        <span>Lihat Penilaian</span>
                    </Button>
                </Link>
                <Link
                    :href="`/courses/${courseId}/assessments/create`"
                    class="block w-full"
                >
                    <Button class="w-full justify-start gap-2">
                        <Plus class="h-4 w-4" />
                        <span>Buat Penilaian</span>
                    </Button>
                </Link>
            </div>
        </FormSection>
    </div>
</template>
