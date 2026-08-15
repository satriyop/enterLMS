<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/crud/PageHeader.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
import { index as learningPathsIndex } from '@/routes/learning-paths';
import { index as usersIndex } from '@/routes/admin/users';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, GraduationCap, Users } from 'lucide-vue-next';

// =============================================================================
// Page-Specific Types
// =============================================================================

/** Dashboard statistics */
interface DashboardStats {
    programs: number;
    courses: number;
    learners: number;
}

interface Props {
    stats: DashboardStats;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6"
        >
            <PageHeader
                eyebrow="Ringkasan"
                title="Dashboard"
                description="Angka di bawah merangkum program, kursus, dan peserta yang ada di academy."
            />

            <div class="grid gap-4 md:grid-cols-3">
                <!-- Programs Widget -->
                <Link :href="learningPathsIndex().url" class="block transition-transform hover:scale-[1.02]">
                    <Card class="h-full cursor-pointer hover:border-primary/50">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium">
                                Program
                            </CardTitle>
                            <GraduationCap class="h-5 w-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-stat">
                                {{ props.stats.programs }}
                            </div>
                            <CardDescription class="mt-1">
                                Total program pembelajaran
                            </CardDescription>
                        </CardContent>
                    </Card>
                </Link>

                <!-- Courses Widget -->
                <Link :href="coursesIndex().url" class="block transition-transform hover:scale-[1.02]">
                    <Card class="h-full cursor-pointer hover:border-primary/50">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium">
                                Kursus
                            </CardTitle>
                            <BookOpen class="h-5 w-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-stat">
                                {{ props.stats.courses }}
                            </div>
                            <CardDescription class="mt-1">
                                Total kursus tersedia
                            </CardDescription>
                        </CardContent>
                    </Card>
                </Link>

                <!-- Learners Widget -->
                <Link :href="usersIndex().url" class="block transition-transform hover:scale-[1.02]">
                    <Card class="h-full cursor-pointer hover:border-primary/50">
                        <CardHeader class="flex flex-row items-center justify-between pb-2">
                            <CardTitle class="text-sm font-medium">
                                Peserta
                            </CardTitle>
                            <Users class="h-5 w-5 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-stat">
                                {{ props.stats.learners }}
                            </div>
                            <CardDescription class="mt-1">
                                Total peserta terdaftar
                            </CardDescription>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <div
                class="relative min-h-[50vh] flex-1 rounded-xl border border-sidebar-border/70 p-6 md:min-h-min dark:border-sidebar-border"
            >
                <div class="text-muted-foreground text-center py-12">
                    <p class="text-lg">Selamat datang di Dashboard LMS</p>
                    <p class="text-sm mt-2">Pilih menu di sidebar untuk memulai</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
