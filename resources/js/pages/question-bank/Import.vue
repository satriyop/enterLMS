<script setup lang="ts">
import { index as importIndex, store as importStore } from '@/actions/App/Http/Controllers/QuestionImportController';
import { index as questionsRoute } from '@/actions/App/Http/Controllers/QuestionController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import FilterTabs from '@/components/crud/FilterTabs.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import Pagination from '@/components/crud/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type PaginatedResponse,
    type QuestionBankItem,
    type Tag,
    type QuestionType,
    type DifficultyLevel,
    getQuestionTypeLabel,
    getVisibilityLabel,
    getDifficultyLabel,
    getVisibilityColor,
    getDifficultyColor,
} from '@/types';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { useSearch } from '@/composables/ui/useSearch';
import { HelpCircle, Import, Hash, Star, User, Check } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface Course {
    id: number;
    title: string;
}

interface Assessment {
    id: number;
    title: string;
}

interface Props {
    course: Course;
    assessment: Assessment;
    items: PaginatedResponse<QuestionBankItem>;
    tags: Tag[];
    filters: {
        search?: string;
        type?: QuestionType;
        difficulty?: DifficultyLevel;
        tag_id?: number;
        mine?: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Kursus', href: `/courses/${props.course.id}` },
    { title: 'Penilaian', href: `/courses/${props.course.id}/assessments` },
    { title: props.assessment.title, href: `/courses/${props.course.id}/assessments/${props.assessment.id}` },
    { title: 'Impor dari Bank Soal', href: '#' },
];

// =============================================================================
// State
// =============================================================================

const selectedIds = ref<number[]>([]);
const type = ref<string>(props.filters.type ?? '');
const difficulty = ref<string>(props.filters.difficulty ?? '');
const selectedTagId = ref<number | undefined>(props.filters.tag_id);
const mineOnly = ref(props.filters.mine ?? false);

const { query: search } = useSearch({
    url: () => importIndex({ course: props.course.id, assessment: props.assessment.id }).url,
    initial: props.filters.search ?? '',
    extraParams: () => ({
        type: type.value || undefined,
        difficulty: difficulty.value || undefined,
        tag_id: selectedTagId.value || undefined,
        mine: mineOnly.value || undefined,
    }),
});

const form = useForm({
    question_bank_item_ids: [] as number[],
});

// =============================================================================
// Computed
// =============================================================================

const typeTabs = computed(() => [
    { value: '', label: 'Semua' },
    { value: 'multiple_choice', label: 'Pilihan Ganda' },
    { value: 'true_false', label: 'Benar/Salah' },
    { value: 'short_answer', label: 'Jawaban Singkat' },
    { value: 'essay', label: 'Esai' },
]);

const selectedCount = computed(() => selectedIds.value.length);

const totalPoints = computed(() => {
    return props.items.data
        .filter((item) => selectedIds.value.includes(item.id))
        .reduce((sum, item) => sum + item.default_points, 0);
});

// =============================================================================
// Methods
// =============================================================================

const toggleSelection = (id: number) => {
    const index = selectedIds.value.indexOf(id);
    if (index === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(index, 1);
    }
};

const isSelected = (id: number) => selectedIds.value.includes(id);

const selectAll = () => {
    if (selectedIds.value.length === props.items.data.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.items.data.map((item) => item.id);
    }
};

const handleImport = () => {
    form.question_bank_item_ids = selectedIds.value;
    form.post(importStore({ course: props.course.id, assessment: props.assessment.id }).url);
};

// =============================================================================
// Watchers
// =============================================================================

const applyFilters = () => {
    router.get(
        importIndex({ course: props.course.id, assessment: props.assessment.id }).url,
        {
            search: search.value || undefined,
            type: type.value || undefined,
            difficulty: difficulty.value || undefined,
            tag_id: selectedTagId.value || undefined,
            mine: mineOnly.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch([type, difficulty, selectedTagId, mineOnly], applyFilters);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Impor Pertanyaan - ${assessment.title}`" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                :title="`Impor Pertanyaan - ${assessment.title}`"
                description="Pilih pertanyaan dari bank soal untuk diimpor ke penilaian ini"
                :back-href="questionsRoute({ course: course.id, assessment: assessment.id }).url"
                back-label="Kembali ke Edit Pertanyaan"
            />

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <FilterTabs v-model="type" :tabs="typeTabs" />

                <div class="flex flex-wrap items-center gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            v-model="mineOnly"
                            class="rounded border-border"
                        />
                        <span>Milik saya</span>
                    </label>

                    <select
                        v-model="selectedTagId"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option :value="undefined">Semua Tag</option>
                        <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                            {{ tag.name }}
                        </option>
                    </select>

                    <select
                        v-model="difficulty"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option value="">Semua Kesulitan</option>
                        <option value="beginner">Pemula</option>
                        <option value="intermediate">Menengah</option>
                        <option value="advanced">Lanjutan</option>
                    </select>

                    <div class="w-full lg:w-64">
                        <SearchInput v-model="search" placeholder="Cari pertanyaan..." />
                    </div>
                </div>
            </div>

            <!-- Selection Summary & Import Button -->
            <div
                v-if="selectedCount > 0"
                class="sticky top-0 z-10 flex items-center justify-between rounded-lg border bg-primary/5 p-4"
            >
                <div class="flex items-center gap-4">
                    <Badge variant="secondary" class="text-base">
                        {{ selectedCount }} dipilih
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                        Total: {{ totalPoints }} poin
                    </span>
                </div>
                <Button @click="handleImport" :disabled="form.processing">
                    <Import class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Mengimpor...' : 'Impor Pertanyaan' }}
                </Button>
            </div>

            <EmptyState
                v-if="items.data.length === 0"
                :icon="HelpCircle"
                title="Tidak ada pertanyaan ditemukan"
                description="Coba ubah filter pencarian atau buat pertanyaan baru di bank soal."
            />

            <template v-else>
                <!-- Select All -->
                <div class="flex items-center gap-3 border-b pb-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        @click="selectAll"
                    >
                        <Checkbox :checked="selectedIds.length === items.data.length && items.data.length > 0" />
                        Pilih Semua
                    </button>
                </div>

                <!-- Question List -->
                <div class="space-y-3">
                    <div
                        v-for="item in items.data"
                        :key="item.id"
                        class="relative flex cursor-pointer gap-4 rounded-xl border bg-card p-4 transition-all hover:shadow-md"
                        :class="isSelected(item.id) ? 'border-primary ring-2 ring-primary/20' : ''"
                        @click="toggleSelection(item.id)"
                    >
                        <!-- Checkbox -->
                        <div class="flex shrink-0 items-start pt-1">
                            <Checkbox :checked="isSelected(item.id)" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
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

                            <p class="mb-2 line-clamp-2 text-sm">
                                {{ item.question_text }}
                            </p>

                            <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Hash class="h-3 w-3" />
                                    {{ item.default_points }} poin
                                </span>
                                <span class="flex items-center gap-1">
                                    <Star class="h-3 w-3" />
                                    Digunakan {{ item.times_used }}x
                                </span>
                                <span class="flex items-center gap-1">
                                    <User class="h-3 w-3" />
                                    {{ item.user?.name ?? 'Unknown' }}
                                </span>
                            </div>

                            <!-- Tags -->
                            <div v-if="item.tags && item.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                                <Badge v-for="tag in item.tags" :key="tag.id" variant="outline" class="text-xs">
                                    {{ tag.name }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Selected Indicator -->
                        <div
                            v-if="isSelected(item.id)"
                            class="absolute right-3 top-3 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground"
                        >
                            <Check class="h-4 w-4" />
                        </div>
                    </div>
                </div>

                <Pagination
                    v-if="items.last_page > 1"
                    :links="items.links"
                    :current-page="items.current_page"
                    :last-page="items.last_page"
                    :from="items.from"
                    :to="items.to"
                    :total="items.total"
                />
            </template>
        </div>
    </AppLayout>
</template>
