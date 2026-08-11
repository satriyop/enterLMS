<script setup lang="ts">
import { index, show, edit, create } from '@/actions/App/Http/Controllers/QuestionBankController';
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
    type PaginatedResponse,
    type QuestionBankItem,
    type Tag,
    type QuestionType,
    type QuestionBankVisibility,
    type DifficultyLevel,
    getQuestionTypeLabel,
    getVisibilityLabel,
    getDifficultyLabel,
    getVisibilityColor,
    getDifficultyColor,
} from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { useSearch } from '@/composables/ui/useSearch';
import { useViewMode } from '@/composables/ui/useViewMode';
import {
    HelpCircle,
    Eye,
    Pencil,
    Copy,
    LayoutGrid,
    List,
    Plus,
    Hash,
    Star,
    User,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface Props {
    items: PaginatedResponse<QuestionBankItem>;
    tags: Tag[];
    filters: {
        search?: string;
        type?: QuestionType;
        difficulty?: DifficultyLevel;
        visibility?: QuestionBankVisibility;
        tag_id?: number;
        mine?: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Bank Soal', href: index().url },
];

// =============================================================================
// State
// =============================================================================

const type = ref<string>(props.filters.type ?? '');
const difficulty = ref<string>(props.filters.difficulty ?? '');
const visibility = ref<string>(props.filters.visibility ?? '');
const selectedTagId = ref<number | undefined>(props.filters.tag_id);
const mineOnly = ref(props.filters.mine ?? false);

const { query: search } = useSearch({
    url: () => index().url,
    initial: props.filters.search ?? '',
    extraParams: () => ({
        type: type.value || undefined,
        difficulty: difficulty.value || undefined,
        visibility: visibility.value || undefined,
        tag_id: selectedTagId.value || undefined,
        mine: mineOnly.value || undefined,
    }),
});

const { viewMode, setMode, containerClasses } = useViewMode({ key: 'question-bank' });

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

// =============================================================================
// Helpers
// =============================================================================

const getBadges = (item: QuestionBankItem) => {
    const badges = [];

    badges.push({
        label: item.question_type_label || getQuestionTypeLabel(item.question_type),
        variant: 'secondary' as const,
    });

    if (item.difficulty_level) {
        badges.push({
            label: item.difficulty_label || getDifficultyLabel(item.difficulty_level) || '',
            class: getDifficultyColor(item.difficulty_level),
        });
    }

    badges.push({
        label: item.visibility_label || getVisibilityLabel(item.visibility),
        class: getVisibilityColor(item.visibility),
    });

    return badges;
};

const getMeta = (item: QuestionBankItem) => [
    { icon: Hash, label: `${item.default_points} poin` },
    { icon: Star, label: `Digunakan ${item.times_used}x` },
    { icon: User, label: item.user?.name ?? 'Unknown' },
];

const getActions = (item: QuestionBankItem) => [
    { label: 'Lihat', href: show({ question_bank: item.id }).url, icon: Eye },
    { label: 'Edit', href: edit({ question_bank: item.id }).url, icon: Pencil },
    { label: 'Duplikat', href: `#duplicate-${item.id}`, icon: Copy, onClick: () => duplicateItem(item.id) },
];

const duplicateItem = (id: number) => {
    router.post(`/question-bank/${id}/duplicate`);
};

// =============================================================================
// Watchers
// =============================================================================

const applyFilters = () => {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            type: type.value || undefined,
            difficulty: difficulty.value || undefined,
            visibility: visibility.value || undefined,
            tag_id: selectedTagId.value || undefined,
            mine: mineOnly.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch([type, difficulty, visibility, selectedTagId, mineOnly], applyFilters);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Bank Soal" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Bank Soal"
                description="Kelola pertanyaan yang dapat digunakan kembali di berbagai penilaian"
            >
                <template #actions>
                    <Link :href="create().url">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Buat Pertanyaan
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <FilterTabs v-model="type" :tabs="typeTabs" />

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Mine only toggle -->
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            v-model="mineOnly"
                            class="rounded border-border"
                        />
                        <span>Milik saya</span>
                    </label>

                    <!-- Tag filter -->
                    <select
                        v-model="selectedTagId"
                        class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option :value="undefined">Semua Tag</option>
                        <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                            {{ tag.name }}
                        </option>
                    </select>

                    <!-- Difficulty filter -->
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

                    <div class="hidden items-center gap-1 rounded-lg border p-1 sm:flex">
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'grid' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('grid')"
                        >
                            <LayoutGrid class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded-md p-2 transition-colors"
                            :class="viewMode === 'list' ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                            @click="setMode('list')"
                        >
                            <List class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="items.data.length === 0"
                :icon="HelpCircle"
                title="Belum ada pertanyaan di bank soal"
                description="Mulai membuat pertanyaan yang dapat digunakan kembali di berbagai penilaian."
                action-label="Buat Pertanyaan"
                :action-href="create().url"
            />

            <template v-else>
                <div :class="containerClasses()">
                    <DataCard
                        v-for="item in items.data"
                        :key="item.id"
                        :title="item.question_text.substring(0, 100) + (item.question_text.length > 100 ? '...' : '')"
                        :description="item.feedback || 'Tidak ada feedback'"
                        :href="show({ question_bank: item.id }).url"
                        :placeholder-icon="HelpCircle"
                        :badges="getBadges(item)"
                        :meta="getMeta(item)"
                        :actions="getActions(item)"
                    />
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
