<script setup lang="ts">
import { update, index, show } from '@/actions/App/Http/Controllers/QuestionBankController';
import PageHeader from '@/components/crud/PageHeader.vue';
import FormSection from '@/components/crud/FormSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    type BreadcrumbItem,
    type Tag,
    type QuestionBankItem,
    type QuestionType,
    type QuestionBankVisibility,
    type DifficultyLevel,
} from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface QuestionOption {
    id?: number;
    option_text: string;
    is_correct: boolean;
    feedback: string;
    order: number;
}

interface FormData {
    question_text: string;
    question_type: QuestionType;
    default_points: number;
    feedback: string;
    correct_answer: string;
    case_sensitive: boolean;
    grading_rubric: string;
    difficulty_level: DifficultyLevel | '';
    visibility: QuestionBankVisibility;
    options: QuestionOption[];
    tag_ids: number[];
}

interface Props {
    item: QuestionBankItem;
    tags: Tag[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Bank Soal', href: index().url },
    { title: props.item.question_text.substring(0, 50), href: show({ question_bank: props.item.id }).url },
    { title: 'Edit', href: '#' },
];

// =============================================================================
// State
// =============================================================================

const form = ref<FormData>({
    question_text: props.item.question_text,
    question_type: props.item.question_type,
    default_points: props.item.default_points,
    feedback: props.item.feedback ?? '',
    correct_answer: props.item.correct_answer ?? '',
    case_sensitive: props.item.case_sensitive,
    grading_rubric: props.item.grading_rubric ?? '',
    difficulty_level: props.item.difficulty_level ?? '',
    visibility: props.item.visibility,
    options: props.item.options?.map((opt) => ({
        id: opt.id,
        option_text: opt.option_text,
        is_correct: opt.is_correct,
        feedback: opt.feedback ?? '',
        order: opt.order,
    })) ?? [],
    tag_ids: props.item.tags?.map((t) => t.id) ?? [],
});

// =============================================================================
// Computed
// =============================================================================

const showOptionsSection = computed(() => {
    return ['multiple_choice', 'true_false'].includes(form.value.question_type);
});

const showCorrectAnswerField = computed(() => {
    return ['short_answer', 'true_false'].includes(form.value.question_type);
});

const showRubricField = computed(() => {
    return ['essay', 'file_upload'].includes(form.value.question_type);
});

const questionTypeOptions = [
    { value: 'multiple_choice', label: 'Pilihan Ganda' },
    { value: 'true_false', label: 'Benar/Salah' },
    { value: 'short_answer', label: 'Jawaban Singkat' },
    { value: 'essay', label: 'Esai' },
    { value: 'file_upload', label: 'Unggah Berkas' },
];

const visibilityOptions = [
    { value: 'private', label: 'Pribadi', description: 'Hanya Anda yang dapat melihat' },
    { value: 'department', label: 'Departemen', description: 'Instruktur dan content manager dapat melihat' },
    { value: 'public', label: 'Publik', description: 'Semua pembuat konten dapat melihat' },
];

const difficultyOptions = [
    { value: '', label: 'Tidak ditentukan' },
    { value: 'beginner', label: 'Pemula' },
    { value: 'intermediate', label: 'Menengah' },
    { value: 'advanced', label: 'Lanjutan' },
];

// =============================================================================
// Methods
// =============================================================================

const addOption = () => {
    form.value.options.push({
        option_text: '',
        is_correct: false,
        feedback: '',
        order: form.value.options.length,
    });
};

const removeOption = (index: number) => {
    if (form.value.options.length > 2) {
        form.value.options.splice(index, 1);
        form.value.options.forEach((opt, i) => {
            opt.order = i;
        });
    }
};

const setCorrectOption = (index: number) => {
    if (form.value.question_type === 'multiple_choice' || form.value.question_type === 'true_false') {
        form.value.options.forEach((opt, i) => {
            opt.is_correct = i === index;
        });
    }
};

watch(() => form.value.question_type, (newType, oldType) => {
    if (newType === oldType) return;

    if (newType === 'true_false') {
        form.value.options = [
            { option_text: 'Benar', is_correct: true, feedback: '', order: 0 },
            { option_text: 'Salah', is_correct: false, feedback: '', order: 1 },
        ];
    } else if (newType === 'multiple_choice' && form.value.options.length < 2) {
        form.value.options = [
            { option_text: '', is_correct: false, feedback: '', order: 0 },
            { option_text: '', is_correct: false, feedback: '', order: 1 },
        ];
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Edit Pertanyaan Bank Soal" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Edit Pertanyaan"
                description="Perbarui pertanyaan di bank soal"
                :back-href="show({ question_bank: item.id }).url"
                back-label="Kembali ke Detail"
            />

            <Form
                v-bind="update.form({ question_bank: item.id })"
                :data="form"
                class="grid gap-6 lg:grid-cols-3"
                v-slot="{ errors, processing }"
            >
                <div class="space-y-6 lg:col-span-2">
                    <!-- Question Content -->
                    <FormSection title="Konten Pertanyaan" description="Teks pertanyaan dan tipe">
                        <div class="space-y-5">
                            <div class="space-y-2">
                                <Label for="question_type" class="text-sm font-medium">
                                    Tipe Pertanyaan <span class="text-destructive">*</span>
                                </Label>
                                <select
                                    id="question_type"
                                    name="question_type"
                                    v-model="form.question_type"
                                    class="flex h-11 w-full rounded-lg border border-input bg-background px-4 py-2 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary"
                                >
                                    <option v-for="opt in questionTypeOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.question_type" />
                            </div>

                            <div class="space-y-2">
                                <Label for="question_text" class="text-sm font-medium">
                                    Teks Pertanyaan <span class="text-destructive">*</span>
                                </Label>
                                <textarea
                                    id="question_text"
                                    name="question_text"
                                    v-model="form.question_text"
                                    rows="4"
                                    class="flex w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary"
                                    placeholder="Tulis pertanyaan Anda di sini..."
                                    required
                                />
                                <InputError :message="errors.question_text" />
                            </div>

                            <div class="space-y-2">
                                <Label for="feedback" class="text-sm font-medium">
                                    Feedback (Opsional)
                                </Label>
                                <textarea
                                    id="feedback"
                                    name="feedback"
                                    v-model="form.feedback"
                                    rows="2"
                                    class="flex w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary"
                                    placeholder="Feedback yang akan ditampilkan setelah menjawab"
                                />
                                <InputError :message="errors.feedback" />
                            </div>
                        </div>
                    </FormSection>

                    <!-- Options Section -->
                    <FormSection
                        v-if="showOptionsSection"
                        title="Opsi Jawaban"
                        description="Tambahkan opsi jawaban dan tandai yang benar"
                    >
                        <div class="space-y-4">
                            <div
                                v-for="(option, index) in form.options"
                                :key="index"
                                class="flex items-start gap-3 rounded-lg border p-4"
                            >
                                <input
                                    type="radio"
                                    :name="`correct_option`"
                                    :checked="option.is_correct"
                                    @change="setCorrectOption(index)"
                                    class="mt-3 h-4 w-4"
                                />
                                <div class="flex-1 space-y-2">
                                    <Input
                                        v-model="option.option_text"
                                        :placeholder="`Opsi ${String.fromCharCode(65 + index)}`"
                                        :disabled="form.question_type === 'true_false'"
                                        class="h-10"
                                    />
                                    <Input
                                        v-model="option.feedback"
                                        placeholder="Feedback untuk opsi ini (opsional)"
                                        class="h-9 text-sm"
                                    />
                                </div>
                                <Button
                                    v-if="form.options.length > 2 && form.question_type !== 'true_false'"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    @click="removeOption(index)"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <Button
                                v-if="form.question_type !== 'true_false'"
                                type="button"
                                variant="outline"
                                class="w-full"
                                @click="addOption"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Opsi
                            </Button>

                            <!-- Hidden inputs for options -->
                            <template v-for="(option, index) in form.options" :key="`hidden-${index}`">
                                <input v-if="option.id" type="hidden" :name="`options[${index}][id]`" :value="option.id" />
                                <input type="hidden" :name="`options[${index}][option_text]`" :value="option.option_text" />
                                <input type="hidden" :name="`options[${index}][is_correct]`" :value="option.is_correct ? '1' : '0'" />
                                <input type="hidden" :name="`options[${index}][feedback]`" :value="option.feedback" />
                                <input type="hidden" :name="`options[${index}][order]`" :value="option.order" />
                            </template>
                        </div>
                    </FormSection>

                    <!-- Short Answer Section -->
                    <FormSection
                        v-if="showCorrectAnswerField"
                        title="Jawaban Benar"
                        description="Jawaban yang dianggap benar untuk pertanyaan ini"
                    >
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="correct_answer" class="text-sm font-medium">
                                    Jawaban Benar
                                </Label>
                                <Input
                                    id="correct_answer"
                                    name="correct_answer"
                                    v-model="form.correct_answer"
                                    placeholder="Pisahkan beberapa jawaban dengan koma"
                                    class="h-11"
                                />
                                <InputError :message="errors.correct_answer" />
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="case_sensitive"
                                    name="case_sensitive"
                                    v-model="form.case_sensitive"
                                    class="h-4 w-4 rounded border-border"
                                />
                                <Label for="case_sensitive" class="text-sm">
                                    Peka huruf besar/kecil
                                </Label>
                            </div>
                        </div>
                    </FormSection>

                    <!-- Rubric Section -->
                    <FormSection
                        v-if="showRubricField"
                        title="Rubrik Penilaian"
                        description="Panduan untuk penilaian manual"
                    >
                        <div class="space-y-2">
                            <Label for="grading_rubric" class="text-sm font-medium">
                                Rubrik Penilaian
                            </Label>
                            <textarea
                                id="grading_rubric"
                                name="grading_rubric"
                                v-model="form.grading_rubric"
                                rows="5"
                                class="flex w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/20 focus-visible:border-primary"
                                placeholder="Kriteria penilaian untuk pertanyaan ini..."
                            />
                            <InputError :message="errors.grading_rubric" />
                        </div>
                    </FormSection>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <div class="rounded-xl border bg-card p-4 shadow-sm">
                        <h3 class="mb-4 font-semibold">Pengaturan</h3>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label for="default_points" class="text-sm font-medium">
                                    Poin Default <span class="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="default_points"
                                    name="default_points"
                                    v-model="form.default_points"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="h-10"
                                    required
                                />
                                <InputError :message="errors.default_points" />
                            </div>

                            <div class="space-y-2">
                                <Label for="difficulty_level" class="text-sm font-medium">
                                    Tingkat Kesulitan
                                </Label>
                                <select
                                    id="difficulty_level"
                                    name="difficulty_level"
                                    v-model="form.difficulty_level"
                                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option v-for="opt in difficultyOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <InputError :message="errors.difficulty_level" />
                            </div>

                            <div class="space-y-2">
                                <Label class="text-sm font-medium">Visibilitas <span class="text-destructive">*</span></Label>
                                <div class="space-y-2">
                                    <label
                                        v-for="opt in visibilityOptions"
                                        :key="opt.value"
                                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors"
                                        :class="form.visibility === opt.value ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'"
                                    >
                                        <input
                                            type="radio"
                                            name="visibility"
                                            :value="opt.value"
                                            v-model="form.visibility"
                                            class="mt-0.5 h-4 w-4"
                                        />
                                        <div>
                                            <div class="text-sm font-medium">{{ opt.label }}</div>
                                            <div class="text-xs text-muted-foreground">{{ opt.description }}</div>
                                        </div>
                                    </label>
                                </div>
                                <InputError :message="errors.visibility" />
                            </div>

                            <!-- Tags -->
                            <div class="space-y-2">
                                <Label class="text-sm font-medium">Tag</Label>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="tag in tags"
                                        :key="tag.id"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-full border px-3 py-1 text-sm transition-colors"
                                        :class="form.tag_ids.includes(tag.id) ? 'border-primary bg-primary/10 text-primary' : 'hover:bg-muted'"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="tag.id"
                                            v-model="form.tag_ids"
                                            class="sr-only"
                                        />
                                        {{ tag.name }}
                                    </label>
                                </div>
                                <input
                                    v-for="tagId in form.tag_ids"
                                    :key="tagId"
                                    type="hidden"
                                    name="tag_ids[]"
                                    :value="tagId"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="sticky bottom-4 flex gap-3 rounded-xl border bg-card p-4 shadow-lg">
                        <Link :href="show({ question_bank: item.id }).url" class="flex-1">
                            <Button type="button" variant="outline" class="h-11 w-full">
                                Batal
                            </Button>
                        </Link>
                        <Button type="submit" class="h-11 flex-1" :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </Button>
                    </div>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
