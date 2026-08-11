<script setup lang="ts">
// =============================================================================
// User Certificates Page
// Displays list of earned certificates with download/view options
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { Award, Download, Eye, ExternalLink, Calendar, GraduationCap } from 'lucide-vue-next';
import { computed } from 'vue';
import { formatDate } from '@/lib/date';
import { stream, download, verify } from '@/actions/App/Http/Controllers/CertificateController';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';

// =============================================================================
// Types
// =============================================================================

interface CertificateItem {
    id: number;
    certificate_number: string;
    type: 'course_completion' | 'assessment_pass' | 'learning_path_completion';
    status: 'active' | 'revoked' | 'expired';
    recipient_name: string;
    certificable_title: string;
    grade: number | null;
    progress_percentage: number | null;
    issued_at: string;
    expires_at: string | null;
    verification_code: string;
}

interface Props {
    certificates: CertificateItem[];
}

const props = defineProps<Props>();

// =============================================================================
// Computed
// =============================================================================

const hasCertificates = computed(() => props.certificates.length > 0);

// =============================================================================
// Helpers
// =============================================================================

const getTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
        course_completion: 'Penyelesaian Kursus',
        assessment_pass: 'Kelulusan Ujian',
        learning_path_completion: 'Penyelesaian Learning Path',
    };
    return labels[type] || type;
};

const getTypeBadgeVariant = (type: string): 'default' | 'secondary' | 'outline' => {
    const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
        course_completion: 'default',
        assessment_pass: 'secondary',
        learning_path_completion: 'outline',
    };
    return variants[type] || 'default';
};

const getStatusBadge = (status: string) => {
    const badges: Record<string, { label: string; variant: 'default' | 'destructive' | 'secondary' }> = {
        active: { label: 'Aktif', variant: 'default' },
        revoked: { label: 'Dicabut', variant: 'destructive' },
        expired: { label: 'Kedaluwarsa', variant: 'secondary' },
    };
    return badges[status] || { label: status, variant: 'secondary' };
};
</script>

<template>
    <Head title="Sertifikat Saya" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Navbar />

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30">
                        <Award class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Sertifikat Saya
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Koleksi sertifikat yang telah Anda peroleh
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="!hasCertificates"
                title="Belum ada sertifikat"
                description="Selesaikan kursus untuk mendapatkan sertifikat pencapaian Anda."
                :icon="Award"
            >
                <template #action>
                    <Button as-child>
                        <Link :href="coursesIndex.url()">Jelajahi Kursus</Link>
                    </Button>
                </template>
            </EmptyState>

            <!-- Certificates Grid -->
            <div v-else class="grid gap-4 sm:grid-cols-2">
                <Card
                    v-for="certificate in certificates"
                    :key="certificate.id"
                    class="overflow-hidden transition-shadow hover:shadow-lg"
                >
                    <!-- Certificate Header with gradient -->
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-3">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2">
                                <GraduationCap class="h-5 w-5 text-white" />
                                <span class="text-sm font-medium text-white">
                                    {{ getTypeLabel(certificate.type) }}
                                </span>
                            </div>
                            <Badge :variant="getStatusBadge(certificate.status).variant" class="text-xs">
                                {{ getStatusBadge(certificate.status).label }}
                            </Badge>
                        </div>
                    </div>

                    <CardContent class="p-4">
                        <!-- Certificate Title -->
                        <h3 class="mb-2 line-clamp-2 text-lg font-semibold text-gray-900 dark:text-white">
                            {{ certificate.certificable_title }}
                        </h3>

                        <!-- Certificate Details -->
                        <div class="mb-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                <span>Diterbitkan: {{ formatDate(certificate.issued_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Award class="h-4 w-4" />
                                <span class="font-mono text-xs">{{ certificate.certificate_number }}</span>
                            </div>
                            <div v-if="certificate.grade" class="flex items-center gap-2">
                                <span class="font-medium">Nilai: {{ certificate.grade }}%</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                as-child
                                class="flex-1"
                            >
                                <a
                                    :href="stream.url(certificate.id)"
                                    target="_blank"
                                >
                                    <Eye class="mr-1.5 h-4 w-4" />
                                    Lihat
                                </a>
                            </Button>
                            <Button
                                size="sm"
                                as-child
                                class="flex-1"
                            >
                                <a :href="download.url(certificate.id)">
                                    <Download class="mr-1.5 h-4 w-4" />
                                    Unduh
                                </a>
                            </Button>
                        </div>

                        <!-- Verification Link -->
                        <div class="mt-3 border-t pt-3 dark:border-gray-700">
                            <Link
                                :href="verify.url(certificate.verification_code)"
                                class="flex items-center gap-1 text-xs text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400"
                            >
                                <ExternalLink class="h-3 w-3" />
                                Verifikasi sertifikat ini
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </main>

        <Footer />
    </div>
</template>
