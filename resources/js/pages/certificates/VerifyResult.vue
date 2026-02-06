<script setup lang="ts">
// =============================================================================
// Certificate Verification Result Page
// Shows the result of certificate verification
// =============================================================================

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, XCircle, AlertCircle, Calendar, Award, User, GraduationCap, ArrowLeft } from 'lucide-vue-next';
import { computed } from 'vue';

// =============================================================================
// Types
// =============================================================================

interface CertificateInfo {
    certificate_number: string;
    recipient_name: string;
    certificable_title: string;
    issued_at: string;
    status: 'active' | 'revoked' | 'expired';
    grade: number | null;
}

interface Props {
    valid: boolean;
    message: string;
    certificate: CertificateInfo | null;
}

const props = defineProps<Props>();

// =============================================================================
// Computed
// =============================================================================

const statusConfig = computed(() => {
    if (props.valid) {
        return {
            icon: CheckCircle,
            bgColor: 'bg-green-100 dark:bg-green-900/30',
            iconColor: 'text-green-600 dark:text-green-400',
            borderColor: 'border-green-200 dark:border-green-800',
            textColor: 'text-green-800 dark:text-green-200',
        };
    }

    if (props.certificate?.status === 'revoked') {
        return {
            icon: XCircle,
            bgColor: 'bg-red-100 dark:bg-red-900/30',
            iconColor: 'text-red-600 dark:text-red-400',
            borderColor: 'border-red-200 dark:border-red-800',
            textColor: 'text-red-800 dark:text-red-200',
        };
    }

    return {
        icon: AlertCircle,
        bgColor: 'bg-amber-100 dark:bg-amber-900/30',
        iconColor: 'text-amber-600 dark:text-amber-400',
        borderColor: 'border-amber-200 dark:border-amber-800',
        textColor: 'text-amber-800 dark:text-amber-200',
    };
});

const StatusIcon = computed(() => statusConfig.value.icon);
</script>

<template>
    <Head :title="valid ? 'Sertifikat Valid' : 'Sertifikat Tidak Valid'" />

    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 dark:bg-gray-900">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <!-- Status Icon -->
                <div
                    :class="[
                        'mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full',
                        statusConfig.bgColor,
                    ]"
                >
                    <component
                        :is="StatusIcon"
                        :class="['h-10 w-10', statusConfig.iconColor]"
                    />
                </div>

                <CardTitle class="text-2xl">
                    {{ valid ? 'Sertifikat Valid' : 'Sertifikat Tidak Valid' }}
                </CardTitle>
                <CardDescription :class="statusConfig.textColor">
                    {{ message }}
                </CardDescription>
            </CardHeader>

            <CardContent>
                <!-- Certificate Details (if found) -->
                <div
                    v-if="certificate"
                    :class="[
                        'mb-6 rounded-lg border p-4',
                        statusConfig.borderColor,
                        statusConfig.bgColor,
                    ]"
                >
                    <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">
                        Detail Sertifikat
                    </h4>

                    <div class="space-y-3 text-sm">
                        <!-- Recipient -->
                        <div class="flex items-start gap-3">
                            <User class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Penerima</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ certificate.recipient_name }}
                                </p>
                            </div>
                        </div>

                        <!-- Course/Program -->
                        <div class="flex items-start gap-3">
                            <GraduationCap class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Program</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ certificate.certificable_title }}
                                </p>
                            </div>
                        </div>

                        <!-- Certificate Number -->
                        <div class="flex items-start gap-3">
                            <Award class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nomor Sertifikat</p>
                                <p class="font-mono text-sm font-medium text-gray-900 dark:text-white">
                                    {{ certificate.certificate_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Issue Date -->
                        <div class="flex items-start gap-3">
                            <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tanggal Terbit</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ certificate.issued_at }}
                                </p>
                            </div>
                        </div>

                        <!-- Grade (if available) -->
                        <div v-if="certificate.grade" class="flex items-start gap-3">
                            <Award class="mt-0.5 h-4 w-4 shrink-0 text-gray-500" />
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nilai</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ certificate.grade }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-2">
                    <Button as-child variant="outline" class="w-full">
                        <Link href="/certificates/verify">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Verifikasi Sertifikat Lain
                        </Link>
                    </Button>

                    <Button as-child variant="ghost" class="w-full">
                        <Link href="/">
                            Kembali ke Beranda
                        </Link>
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
