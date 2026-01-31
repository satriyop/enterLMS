<script setup lang="ts">
// =============================================================================
// Learning Path Edit Page
// Edit existing learning path with courses
// =============================================================================

import { show, update } from '@/actions/App/Http/Controllers/LearningPathController';
import PageHeader from '@/components/crud/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import LearningPathObjectivesField from '@/components/learning_paths/LearningPathObjectivesField.vue';
import LearningPathCoursesManager from '@/components/learning_paths/LearningPathCoursesManager.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type DifficultyLevel } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface CoursePrerequisites {
    completed_courses: number[];
}

interface CoursePivot {
    is_required: boolean;
    min_completion_percentage: number;
    prerequisites: CoursePrerequisites | null;
}

interface EditableCourse {
    id: number;
    title: string;
    description: string | null;
    slug: string;
    estimated_duration: number;
    difficulty_level: DifficultyLevel;
    thumbnail_url: string | null;
    pivot: CoursePivot;
}

interface EditableLearningPath {
    id: number;
    title: string;
    description: string | null;
    objectives: string[];
    slug: string;
    estimated_duration: number;
    difficulty_level: DifficultyLevel | 'expert';
    thumbnail_url: string | null;
    courses: EditableCourse[];
}

interface SelectedCourse {
    id: number;
    title: string;
    is_required: boolean;
    min_completion_percentage: number;
    prerequisites: CoursePrerequisites | null;
}

interface Props {
    learningPath: EditableLearningPath;
    availableCourses: EditableCourse[];
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Jalur Pembelajaran', href: '/learning-paths' },
    { title: props.learningPath.title, href: show(props.learningPath.id).url },
];

// =============================================================================
// Form State
// =============================================================================

const objectives = ref<string[]>(props.learningPath.objectives || ['']);
const selectedDifficulty = ref<DifficultyLevel | 'expert'>(props.learningPath.difficulty_level || 'beginner');

// =============================================================================
// Course Management
// =============================================================================

const availableCoursesList = ref(
    props.availableCourses
        .filter(course => !props.learningPath.courses.some(lpCourse => lpCourse.id === course.id))
        .map(course => ({ id: course.id, title: course.title }))
);

const selectedCourses = ref<SelectedCourse[]>(
    props.learningPath.courses.map(course => ({
        id: course.id,
        title: course.title,
        is_required: course.pivot.is_required,
        min_completion_percentage: course.pivot.min_completion_percentage || 80,
        prerequisites: course.pivot.prerequisites,
    }))
);

const handleAddCourse = (course: { id: number; title: string }) => {
    selectedCourses.value.push({
        id: course.id,
        title: course.title,
        is_required: true,
        min_completion_percentage: 80,
        prerequisites: null,
    });
    const index = availableCoursesList.value.findIndex(c => c.id === course.id);
    if (index !== -1) availableCoursesList.value.splice(index, 1);
};

const handleRemoveCourse = (course: SelectedCourse) => {
    const originalCourse = props.availableCourses.find(c => c.id === course.id);
    if (originalCourse) {
        availableCoursesList.value.push({ id: originalCourse.id, title: originalCourse.title });
    }
    const index = selectedCourses.value.findIndex(c => c.id === course.id);
    if (index !== -1) selectedCourses.value.splice(index, 1);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Edit: ${learningPath.title}`" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
            <PageHeader
                title="Edit Jalur Pembelajaran"
                :description="learningPath.title"
                :back-href="show(learningPath.id).url"
                back-label="Kembali"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Informasi Jalur Pembelajaran</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="update(learningPath.id).form()"
                        class="space-y-6"
                        enctype="multipart/form-data"
                        #default="{ errors, processing }"
                    >
                        <input type="hidden" name="_method" value="PUT" />

                        <div class="grid gap-2">
                            <Label for="title">Judul *</Label>
                            <Input id="title" name="title" type="text" :value="learningPath.title" required autofocus />
                            <InputError :message="errors.title" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                name="description"
                                class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                rows="4"
                                :value="learningPath.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <LearningPathObjectivesField v-model="objectives" :error="errors.objectives" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="estimated_duration">Durasi Perkiraan (menit)</Label>
                                <Input
                                    id="estimated_duration"
                                    name="estimated_duration"
                                    type="number"
                                    min="1"
                                    :value="learningPath.estimated_duration"
                                />
                                <InputError :message="errors.estimated_duration" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="difficulty_level">Tingkat Kesulitan</Label>
                                <input type="hidden" name="difficulty_level" :value="selectedDifficulty" />
                                <select
                                    id="difficulty_level"
                                    v-model="selectedDifficulty"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="beginner">Pemula</option>
                                    <option value="intermediate">Menengah</option>
                                    <option value="advanced">Lanjutan</option>
                                    <option value="expert">Ahli</option>
                                </select>
                                <InputError :message="errors.difficulty_level" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="thumbnail">Thumbnail</Label>
                            <Input
                                id="thumbnail"
                                name="thumbnail"
                                type="file"
                                accept="image/*"
                            />
                            <InputError :message="errors.thumbnail" />
                        </div>

                        <LearningPathCoursesManager
                            :available-courses="availableCoursesList"
                            v-model:selected-courses="selectedCourses"
                            @add-course="handleAddCourse"
                            @remove-course="handleRemoveCourse"
                        />

                        <!-- Hidden input for courses array -->
                        <input
                            v-for="(course, index) in selectedCourses"
                            :key="course.id"
                            type="hidden"
                            :name="`courses[${index}][id]`"
                            :value="course.id"
                        />
                        <input
                            v-for="(course, index) in selectedCourses"
                            :key="`${course.id}-required`"
                            type="hidden"
                            :name="`courses[${index}][is_required]`"
                            :value="course.is_required ? 1 : 0"
                        />
                        <input
                            v-for="(course, index) in selectedCourses"
                            :key="`${course.id}-completion`"
                            type="hidden"
                            :name="`courses[${index}][min_completion_percentage]`"
                            :value="course.min_completion_percentage"
                        />
                        <input
                            v-for="(course, index) in selectedCourses"
                            :key="`${course.id}-prereqs`"
                            type="hidden"
                            :name="`courses[${index}][prerequisites]`"
                            :value="course.prerequisites ? JSON.stringify(course.prerequisites) : ''"
                        />

                        <div class="flex justify-end gap-4 mt-6">
                            <Link :href="show(learningPath.id).url">
                                <Button type="button" variant="outline">Batal</Button>
                            </Link>
                            <Button type="submit" :disabled="processing">
                                <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                                Perbarui Jalur Pembelajaran
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
