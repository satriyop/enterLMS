<script setup lang="ts">
import {
    accept as acceptProposal,
    reject as rejectProposal,
    store as askProposal,
} from '@/actions/App/Http/Controllers/ContentProposalController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm, router } from '@inertiajs/vue3';

interface ProposalLesson {
    id: number;
    title: string;
    content_type: string;
}

interface ContentProposalItem {
    id: number;
    lesson_id: number;
    lesson_title: string | null;
    instruction: string;
    grounding_body: string;
    proposed_body_text: string | null;
    reason: string | null;
    status: string;
}

const props = defineProps<{
    courseId: number;
    lessons: ProposalLesson[];
    proposals: ContentProposalItem[];
    canPropose: boolean;
}>();

const textLessons = props.lessons.filter((lesson) => lesson.content_type === 'text');

const form = useForm({
    lesson_id: textLessons[0]?.id ?? '',
    instruction: '',
});

const statusLabel: Record<string, string> = {
    asking: 'Menunggu Author Agent',
    pending: 'Menunggu keputusan',
    accepted: 'Diterima',
    rejected: 'Ditolak',
    failed: 'Gagal',
};

function submitAsk(): void {
    form.post(askProposal.url(props.courseId), {
        preserveScroll: true,
    });
}

function accept(id: number): void {
    router.post(acceptProposal.url({ course: props.courseId, contentProposal: id }), {}, { preserveScroll: true });
}

function reject(id: number): void {
    router.post(rejectProposal.url({ course: props.courseId, contentProposal: id }), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-4">
        <Card v-if="canPropose">
            <CardHeader>
                <CardTitle>Minta usulan konten</CardTitle>
                <CardDescription>
                    Author Agent mengusulkan isi Lesson. Belum menjadi Lesson sampai kamu menerima.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="proposal-lesson">Pelajaran teks</Label>
                    <select
                        id="proposal-lesson"
                        v-model="form.lesson_id"
                        class="border-border bg-surface h-9 w-full rounded-md border px-3 text-sm"
                    >
                        <option v-for="lesson in textLessons" :key="lesson.id" :value="lesson.id">
                            {{ lesson.title }}
                        </option>
                    </select>
                    <InputError :message="form.errors.lesson_id" />
                    <p v-if="textLessons.length === 0" class="text-muted-foreground text-sm">
                        Tidak ada Lesson teks pada kursus ini.
                    </p>
                </div>
                <div class="space-y-2">
                    <Label for="proposal-instruction">Instruksi</Label>
                    <Textarea
                        id="proposal-instruction"
                        v-model="form.instruction"
                        rows="4"
                        placeholder="Contoh: Perjelas bedanya chatbot dan agen untuk pimpinan."
                    />
                    <InputError :message="form.errors.instruction" />
                </div>
                <Button
                    type="button"
                    :disabled="form.processing || textLessons.length === 0"
                    @click="submitAsk"
                >
                    {{ form.processing ? 'Meminta…' : 'Minta usulan' }}
                </Button>
            </CardContent>
        </Card>

        <Card v-for="proposal in proposals" :key="proposal.id">
            <CardHeader>
                <CardTitle class="text-base">
                    {{ proposal.lesson_title ?? `Lesson #${proposal.lesson_id}` }}
                </CardTitle>
                <CardDescription>
                    {{ statusLabel[proposal.status] ?? proposal.status }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <p><span class="font-medium">Instruksi:</span> {{ proposal.instruction }}</p>
                <p v-if="proposal.reason"><span class="font-medium">Alasan:</span> {{ proposal.reason }}</p>
                <div v-if="proposal.proposed_body_text" class="space-y-1">
                    <p class="font-medium">Usulan isi</p>
                    <pre class="bg-surface-2 overflow-x-auto whitespace-pre-wrap rounded-md p-3 text-sm">{{ proposal.proposed_body_text }}</pre>
                </div>
                <div v-if="proposal.status === 'pending'" class="flex flex-wrap gap-2">
                    <Button type="button" size="sm" @click="accept(proposal.id)">Terima</Button>
                    <Button type="button" size="sm" variant="outline" @click="reject(proposal.id)">Tolak</Button>
                </div>
            </CardContent>
        </Card>

        <p v-if="proposals.length === 0" class="text-muted-foreground text-sm">
            Belum ada usulan konten.
        </p>
    </div>
</template>
