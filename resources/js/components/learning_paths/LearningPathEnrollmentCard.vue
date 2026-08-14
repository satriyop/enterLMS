<script setup lang="ts">
// =============================================================================
// LearningPathEnrollmentCard Component
// Displays an enrolled learning path card with progress information
// =============================================================================

import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { BookOpen, CheckCircle, Play, Route } from 'lucide-vue-next';
import {
    LEARNING_PATH_STATE_COLORS,
    enrollmentStateLabel,
    type LearningPathEnrollmentItem,
} from '@/types';

// =============================================================================
// Types
// =============================================================================

interface Props {
    enrollment: LearningPathEnrollmentItem;
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const isCompleted = props.enrollment.state === 'completed';
const isActive = props.enrollment.state === 'active';

const getStateColor = () => {
    const color = LEARNING_PATH_STATE_COLORS[props.enrollment.state];
    return color ? `${color.bg} ${color.text}` : '';
};

const getStateLabel = () => enrollmentStateLabel(props.enrollment.state);

const getButtonLabel = () => {
    if (isCompleted) return 'Lihat';
    if (props.enrollment.progress_percentage > 0) return 'Lanjutkan';
    return 'Mulai';
};
</script>

<template>
    <Card class="group overflow-hidden">
        <Link :href="`/learner/learning-paths/${enrollment.learning_path.id}`">
            <div class="relative aspect-video bg-surface-2">
                <img
                    v-if="enrollment.learning_path.thumbnail_url"
                    :src="enrollment.learning_path.thumbnail_url"
                    :alt="enrollment.learning_path.title"
                    class="h-full w-full object-cover transition-transform group-hover:scale-105"
                />
                <div v-else class="flex h-full items-center justify-center">
                    <Route class="h-12 w-12 text-muted-foreground" />
                </div>
                <Badge
                    class="absolute left-2 top-2"
                    :class="getStateColor()"
                >
                    {{ getStateLabel() }}
                </Badge>
                <Badge
                    v-if="isCompleted"
                    class="absolute right-2 top-2 bg-gold-soft text-gold hover:bg-gold-soft"
                >
                    <CheckCircle class="mr-1 h-3 w-3" />
                    Selesai
                </Badge>
            </div>
        </Link>
        <CardContent class="p-4">
            <Link :href="`/learner/learning-paths/${enrollment.learning_path.id}`">
                <h3 class="font-semibold line-clamp-2 hover:text-primary">
                    {{ enrollment.learning_path.title }}
                </h3>
            </Link>

            <!-- Progress Section -->
            <div class="mt-3 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Progres</span>
                    <span class="font-medium">{{ enrollment.progress_percentage }}%</span>
                </div>
                <Progress :model-value="enrollment.progress_percentage" class="h-2" />
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <BookOpen class="h-3 w-3" />
                    <span>{{ enrollment.completed_courses }}/{{ enrollment.total_courses }} Kursus</span>
                </div>
            </div>

            <Link :href="`/learner/learning-paths/${enrollment.learning_path.id}`" class="mt-4 block">
                <Button class="w-full" :variant="isActive ? 'default' : 'outline'" size="sm">
                    <Play v-if="isActive && enrollment.progress_percentage < 100" class="mr-2 h-4 w-4" />
                    <CheckCircle v-else-if="isCompleted" class="mr-2 h-4 w-4" />
                    {{ getButtonLabel() }}
                </Button>
            </Link>
        </CardContent>
    </Card>
</template>
