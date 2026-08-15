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
import { verifySearch } from '@/actions/App/Http/Controllers/CertificateController';

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
            bgColor: 'bg-gold-soft',
            iconColor: 'text-gold',
            borderColor: 'border-gold',
            textColor: 'text-gold',
        };
    }

    if (props.certificate?.status === 'revoked') {
        return {
            icon: XCircle,
            bgColor: 'bg-danger-soft',
            iconColor: 'text-danger',
            borderColor: 'border-danger',
            textColor: 'text-danger',
        };
    }

    return {
        icon: AlertCircle,
        bgColor: 'bg-warn-soft',
        iconColor: 'text-warn',
        borderColor: 'border-warn',
        textColor: 'text-warn',
    };
});

const StatusIcon = computed(() => statusConfig.value.icon);
</script>

<template>
    <Head :title="valid ? 'Sertifikat Valid' : 'Sertifikat Tidak Valid'" />

    <div class="flex min-h-screen items-center justify-center bg-surface-2 px-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <!-- Status Icon -->
                <div
                    :class="[ 'mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full', statusConfig.bgColor, ]"
                >
                    <component
                        :is="StatusIcon"
                        :class="['h-10 w-10', statusConfig.iconColor]"
                    />
                </div>

                <CardTitle class="text-editorial-h1">
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
                    :class="[ 'mb-6 rounded-lg border p-4', statusConfig.borderColor, statusConfig.bgColor, ]"
                >
                    <h4 class="mb-3 text-sm font-semibold text-foreground">
                        Detail Sertifikat
                    </h4>

                    <div class="space-y-3 text-sm">
                        <!-- Recipient -->
                        <div class="flex items-start gap-3">
                            <User class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Penerima</p>
                                <p class="font-medium text-foreground">
                                    {{ certificate.recipient_name }}
                                </p>
                            </div>
                        </div>

                        <!-- Course/Program -->
                        <div class="flex items-start gap-3">
                            <GraduationCap class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Program</p>
                                <p class="font-medium text-foreground">
                                    {{ certificate.certificable_title }}
                                </p>
                            </div>
                        </div>

                        <!-- Certificate Number -->
                        <div class="flex items-start gap-3">
                            <Award class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Nomor Sertifikat</p>
                                <p class="font-mono text-sm font-medium text-foreground">
                                    {{ certificate.certificate_number }}
                                </p>
                            </div>
                        </div>

                        <!-- Issue Date -->
                        <div class="flex items-start gap-3">
                            <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Tanggal Terbit</p>
                                <p class="font-medium text-foreground">
                                    {{ certificate.issued_at }}
                                </p>
                            </div>
                        </div>

                        <!-- Grade (if available) -->
                        <div v-if="certificate.grade" class="flex items-start gap-3">
                            <Award class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="text-xs text-muted-foreground">Nilai</p>
                                <p class="font-medium text-foreground">
                                    {{ certificate.grade }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-2">
                    <Button as-child variant="outline" class="w-full">
                        <Link :href="verifySearch.url()">
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
