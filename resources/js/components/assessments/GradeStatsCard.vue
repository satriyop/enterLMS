<script setup lang="ts">
// =============================================================================
// GradeStatsCard Component
// Displays grading statistics (correct/wrong/ungraded)
// =============================================================================

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Check, X, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface AnswerState {
    is_correct: boolean | null;
}

interface Props {
    answers: AnswerState[];
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

// =============================================================================
// Computed
// =============================================================================

const correctCount = computed(() => props.answers.filter(a => a.is_correct === true).length);
const wrongCount = computed(() => props.answers.filter(a => a.is_correct === false).length);
const ungradedCount = computed(() => props.answers.filter(a => a.is_correct === null || a.is_correct === undefined).length);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Statistik</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="flex items-center gap-3">
                <Check class="h-5 w-5 text-ok" />
                <div>
                    <p class="text-sm text-muted-foreground">Jawaban Benar</p>
                    <p class="text-stat text-ok">{{ correctCount }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <X class="h-5 w-5 text-danger" />
                <div>
                    <p class="text-sm text-muted-foreground">Jawaban Salah</p>
                    <p class="text-stat text-danger">{{ wrongCount }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <AlertTriangle class="h-5 w-5 text-warn" />
                <div>
                    <p class="text-sm text-muted-foreground">Belum Dinilai</p>
                    <p class="text-stat text-warn">{{ ungradedCount }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
