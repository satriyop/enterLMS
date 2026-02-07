<script setup lang="ts">
import { show, index, edit, destroy } from '@/actions/App/Http/Controllers/QuestionBankController';
import PageHeader from '@/components/crud/PageHeader.vue';
import DataCard from '@/components/crud/DataCard.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type QuestionBankItem,
    getQuestionTypeLabel,
    getVisibilityLabel,
    getDifficultyLabel,
    getVisibilityColor,
    getDifficultyColor,
} from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Copy, Trash2, Hash, Star, Calendar, CheckCircle, XCircle, User } from 'lucide-vue-next';

// =============================================================================
// Types
// =============================================================================

interface Props {
    item: QuestionBankItem;
    can: {
        update: boolean;
        delete: boolean;
        duplicate: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Bank Soal', href: index().url },
    { title: props.item.question_text.substring(0, 50), href: show({ question_bank: props.item.id }).url },
];

// =============================================================================
// Methods
// =============================================================================

const handleDuplicate = () => {
    router.post(`/question-bank/${props.item.id}/duplicate`);
};

const handleDelete = () => {
    if (confirm('Apakah Anda yakin ingin menghapus pertanyaan ini?')) {
        router.delete(destroy({ question_bank: props.item.id }).url);
    }
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${item.question_text.substring(0, 50)} - Bank Soal`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Detail Pertanyaan"
                description="Lihat detail pertanyaan bank soal"
                :back-href="index().url"
                back-label="Kembali ke Bank Soal"
            >
                <template #actions>
                    <div class="flex gap-2">
                        <Button v-if="can.duplicate" variant="outline" @click="handleDuplicate">
                            <Copy class="mr-2 h-4 w-4" />
                            Duplikat
                        </Button>
                        <Link v-if="can.update" :href="edit({ question_bank: item.id }).url">
                            <Button variant="outline">
                                <Pencil class="mr-2 h-4 w-4" />
                                Edit
                            </Button>
                        </Link>
                        <Button v-if="can.delete" variant="destructive" @click="handleDelete">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Hapus
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Question Content -->
                    <div class="rounded-xl border bg-card p-6">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {{ item.question_type_label || getQuestionTypeLabel(item.question_type) }}
                            </Badge>
                            <Badge v-if="item.difficulty_level" :class="getDifficultyColor(item.difficulty_level)">
                                {{ item.difficulty_label || getDifficultyLabel(item.difficulty_level) }}
                            </Badge>
                            <Badge :class="getVisibilityColor(item.visibility)">
                                {{ item.visibility_label || getVisibilityLabel(item.visibility) }}
                            </Badge>
                        </div>

                        <h2 class="mb-4 text-lg font-semibold">Pertanyaan</h2>
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            <p class="whitespace-pre-wrap">{{ item.question_text }}</p>
                        </div>

                        <div v-if="item.feedback" class="mt-6">
                            <h3 class="mb-2 text-sm font-medium text-muted-foreground">Feedback</h3>
                            <p class="text-sm">{{ item.feedback }}</p>
                        </div>
                    </div>

                    <!-- Options (for multiple choice / true-false) -->
                    <div v-if="item.options && item.options.length > 0" class="rounded-xl border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Opsi Jawaban</h2>
                        <div class="space-y-3">
                            <div
                                v-for="(option, index) in item.options"
                                :key="option.id"
                                class="flex items-start gap-3 rounded-lg border p-4"
                                :class="option.is_correct ? 'border-green-500 bg-green-50 dark:bg-green-950/20' : ''"
                            >
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                    {{ String.fromCharCode(65 + index) }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm">{{ option.option_text }}</p>
                                    <p v-if="option.feedback" class="mt-1 text-xs text-muted-foreground">
                                        Feedback: {{ option.feedback }}
                                    </p>
                                </div>
                                <CheckCircle v-if="option.is_correct" class="h-5 w-5 text-green-500" />
                                <XCircle v-else class="h-5 w-5 text-muted-foreground/30" />
                            </div>
                        </div>
                    </div>

                    <!-- Correct Answer (for short answer) -->
                    <div v-if="item.correct_answer" class="rounded-xl border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Jawaban Benar</h2>
                        <p class="text-sm">{{ item.correct_answer }}</p>
                        <p v-if="item.case_sensitive" class="mt-2 text-xs text-muted-foreground">
                            Peka huruf besar/kecil
                        </p>
                    </div>

                    <!-- Grading Rubric (for essay / file upload) -->
                    <div v-if="item.grading_rubric" class="rounded-xl border bg-card p-6">
                        <h2 class="mb-4 text-lg font-semibold">Rubrik Penilaian</h2>
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            <p class="whitespace-pre-wrap">{{ item.grading_rubric }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Stats -->
                    <div class="rounded-xl border bg-card p-4">
                        <h3 class="mb-4 font-semibold">Informasi</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-muted-foreground">
                                    <Hash class="h-4 w-4" />
                                    Poin Default
                                </span>
                                <span class="font-medium">{{ item.default_points }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-muted-foreground">
                                    <Star class="h-4 w-4" />
                                    Digunakan
                                </span>
                                <span class="font-medium">{{ item.times_used }}x</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-muted-foreground">
                                    <User class="h-4 w-4" />
                                    Dibuat oleh
                                </span>
                                <span class="font-medium">{{ item.user?.name ?? 'Unknown' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-2 text-muted-foreground">
                                    <Calendar class="h-4 w-4" />
                                    Dibuat
                                </span>
                                <span class="font-medium">{{ formatDate(item.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="item.tags && item.tags.length > 0" class="rounded-xl border bg-card p-4">
                        <h3 class="mb-4 font-semibold">Tag</h3>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="tag in item.tags" :key="tag.id" variant="outline">
                                {{ tag.name }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Derived Questions Count -->
                    <div v-if="item.derived_questions_count !== undefined" class="rounded-xl border bg-card p-4">
                        <h3 class="mb-2 font-semibold">Penggunaan</h3>
                        <p class="text-sm text-muted-foreground">
                            Diimpor ke <span class="font-medium text-foreground">{{ item.derived_questions_count }}</span> penilaian
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
