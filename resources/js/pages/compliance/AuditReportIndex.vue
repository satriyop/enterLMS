<script setup lang="ts">
// =============================================================================
// Compliance Audit Report Dashboard
// OJK-compliant audit reporting with filtering, summary stats, and export
// =============================================================================

import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/crud/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router } from '@inertiajs/vue3';
import {
    FileText,
    Download,
    Users,
    Activity,
    BookOpen,
    ClipboardCheck,
    Calendar,
    Filter,
    TrendingUp,
    TrendingDown,
    ArrowRight,
    GraduationCap,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { index, exportCsv } from '@/actions/App/Http/Controllers/ComplianceReportController';
import type { BreadcrumbItem } from '@/types';

// =============================================================================
// Types
// =============================================================================

interface Summary {
    total_events: number;
    events_by_type: Record<string, number>;
    events_by_category: Record<string, number>;
    unique_users: number;
    date_range: {
        start: string;
        end: string;
    };
}

interface EnrollmentActivity {
    date: string;
    enrollments: number;
    completions: number;
    drops: number;
    reenrollments: number;
}

interface AssessmentActivity {
    date: string;
    attempts_started: number;
    attempts_submitted: number;
    graded: number;
}

interface Filters {
    start_date: string;
    end_date: string;
}

interface Props {
    summary: Summary;
    enrollmentActivity: EnrollmentActivity[];
    assessmentActivity: AssessmentActivity[];
    filters: Filters;
    eventCategories: Record<string, string[]>;
}

const props = defineProps<Props>();

// =============================================================================
// State
// =============================================================================

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const isFiltering = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Kepatuhan', href: '#' },
    { title: 'Laporan Audit', href: index().url },
];

// =============================================================================
// Computed
// =============================================================================

const categoryLabels: Record<string, string> = {
    enrollment: 'Pendaftaran',
    assessment: 'Penilaian',
    progress: 'Kemajuan',
    learning_path: 'Learning Path',
    course_admin: 'Admin Kursus',
};

const categoryIcons: Record<string, typeof Users> = {
    enrollment: Users,
    assessment: ClipboardCheck,
    progress: TrendingUp,
    learning_path: GraduationCap,
    course_admin: BookOpen,
};

const sortedEnrollmentActivity = computed(() =>
    [...props.enrollmentActivity].slice(0, 10)
);

const sortedAssessmentActivity = computed(() =>
    [...props.assessmentActivity].slice(0, 10)
);

// Calculate trend for enrollments
const enrollmentTrend = computed(() => {
    if (props.enrollmentActivity.length < 2) return 0;
    const recent = props.enrollmentActivity.slice(0, 7).reduce((sum, a) => sum + a.enrollments, 0);
    const previous = props.enrollmentActivity.slice(7, 14).reduce((sum, a) => sum + a.enrollments, 0);
    if (previous === 0) return recent > 0 ? 100 : 0;
    return Math.round(((recent - previous) / previous) * 100);
});

// =============================================================================
// Actions
// =============================================================================

const applyFilter = () => {
    isFiltering.value = true;
    router.get(
        index().url,
        { start_date: startDate.value, end_date: endDate.value },
        {
            preserveState: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        }
    );
};

const handleExportCsv = () => {
    window.location.href = `${exportCsv().url}?start_date=${startDate.value}&end_date=${endDate.value}`;
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Laporan Audit Kepatuhan" />

    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                title="Laporan Audit Kepatuhan"
                description="Pantau aktivitas pembelajaran untuk kepatuhan regulasi OJK"
                :icon="FileText"
            >
                <template #actions>
                    <Button variant="outline" @click="handleExportCsv">
                        <Download class="mr-2 h-4 w-4" />
                        Ekspor CSV
                    </Button>
                </template>
            </PageHeader>

            <!-- Date Filter -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Filter class="h-4 w-4" />
                        Filter Periode
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="space-y-2">
                            <Label for="start_date">Tanggal Mulai</Label>
                            <Input
                                id="start_date"
                                v-model="startDate"
                                type="date"
                                class="w-44"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">Tanggal Akhir</Label>
                            <Input
                                id="end_date"
                                v-model="endDate"
                                type="date"
                                class="w-44"
                            />
                        </div>
                        <Button @click="applyFilter" :disabled="isFiltering">
                            {{ isFiltering ? 'Memuat...' : 'Terapkan' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Events -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                <Activity class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Aktivitas</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ summary.total_events.toLocaleString('id-ID') }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Unique Users -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                                <Users class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Pengguna Aktif</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ summary.unique_users.toLocaleString('id-ID') }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Enrollment Trend -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'rounded-lg p-2',
                                    enrollmentTrend >= 0
                                        ? 'bg-emerald-100 dark:bg-emerald-900/30'
                                        : 'bg-red-100 dark:bg-red-900/30',
                                ]"
                            >
                                <component
                                    :is="enrollmentTrend >= 0 ? TrendingUp : TrendingDown"
                                    :class="[
                                        'h-5 w-5',
                                        enrollmentTrend >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-red-600 dark:text-red-400',
                                    ]"
                                />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tren Pendaftaran</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ enrollmentTrend >= 0 ? '+' : '' }}{{ enrollmentTrend }}%
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Period -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                                <Calendar class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Periode</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatDate(summary.date_range.start) }}
                                    <ArrowRight class="mx-1 inline h-3 w-3" />
                                    {{ formatDate(summary.date_range.end) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Category Breakdown -->
            <Card>
                <CardHeader>
                    <CardTitle>Aktivitas per Kategori</CardTitle>
                    <CardDescription>Distribusi aktivitas berdasarkan jenis kegiatan</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div
                            v-for="(count, category) in summary.events_by_category"
                            :key="category"
                            class="flex items-center gap-3 rounded-lg border p-4 dark:border-gray-700"
                        >
                            <div class="rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                <component
                                    :is="categoryIcons[category] || Activity"
                                    class="h-5 w-5 text-gray-600 dark:text-gray-400"
                                />
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ categoryLabels[category] || category }}
                                </p>
                                <p class="text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ count.toLocaleString('id-ID') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Activity Tables -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Enrollment Activity -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Aktivitas Pendaftaran
                        </CardTitle>
                        <CardDescription>Pendaftaran, penyelesaian, dan pembatalan per hari</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Daftar</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Selesai</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Batal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="activity in sortedEnrollmentActivity" :key="activity.date" class="border-b last:border-0">
                                        <td class="px-4 py-3 font-medium">{{ formatDate(activity.date) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge variant="secondary">{{ activity.enrollments }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge variant="default" class="bg-green-500">{{ activity.completions }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge v-if="activity.drops > 0" variant="destructive">{{ activity.drops }}</Badge>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                    </tr>
                                    <tr v-if="sortedEnrollmentActivity.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            Tidak ada aktivitas pada periode ini
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Assessment Activity -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ClipboardCheck class="h-5 w-5" />
                            Aktivitas Penilaian
                        </CardTitle>
                        <CardDescription>Ujian dimulai, dikumpulkan, dan dinilai per hari</CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-muted-foreground">Tanggal</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Mulai</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Kumpul</th>
                                        <th class="px-4 py-3 text-right text-sm font-medium text-muted-foreground">Dinilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="activity in sortedAssessmentActivity" :key="activity.date" class="border-b last:border-0">
                                        <td class="px-4 py-3 font-medium">{{ formatDate(activity.date) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge variant="outline">{{ activity.attempts_started }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge variant="secondary">{{ activity.attempts_submitted }}</Badge>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Badge variant="default">{{ activity.graded }}</Badge>
                                        </td>
                                    </tr>
                                    <tr v-if="sortedAssessmentActivity.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            Tidak ada aktivitas pada periode ini
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
