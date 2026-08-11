<script setup lang="ts">
// =============================================================================
// Payment Details Page
// Shows payment details with actions (pay, cancel, etc.)
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { Head, Link, router } from '@inertiajs/vue3';
import { index as paymentsIndex, cancel as paymentCancel } from '@/actions/App/Http/Controllers/PaymentController';
import { show as courseShow } from '@/actions/App/Http/Controllers/CourseController';
import {
    CreditCard,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    ExternalLink,
    ArrowLeft,
    Calendar,
    Receipt,
    Building,
    Hash,
    BookOpen,
    Ban,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { formatDate, formatDateTime } from '@/lib/date';

// =============================================================================
// Types
// =============================================================================

type PaymentStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'expired' | 'refunded' | 'cancelled';

interface Payment {
    id: number;
    invoice_number: string;
    payable_type: string;
    payable_title: string;
    payable_id: number;
    amount: number;
    formatted_amount: string;
    currency: string;
    status: PaymentStatus;
    gateway: string;
    gateway_transaction_id: string | null;
    payment_url: string | null;
    paid_at: string | null;
    expires_at: string | null;
    created_at: string;
    enrollment_id: number | null;
    is_pending: boolean;
    is_processing: boolean;
    is_paid: boolean;
    can_retry: boolean;
}

interface Props {
    payment: Payment;
}

const props = defineProps<Props>();

// =============================================================================
// State
// =============================================================================

const confirmation = useConfirmation();

// =============================================================================
// Computed
// =============================================================================

const isPayable = computed(() => props.payment.is_pending || props.payment.is_processing);
const canCancel = computed(() => props.payment.is_pending);

const statusConfig = computed(() => {
    const configs: Record<PaymentStatus, {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
        icon: typeof Clock;
        bgColor: string;
        iconColor: string;
    }> = {
        pending: {
            label: 'Menunggu Pembayaran',
            variant: 'secondary',
            icon: Clock,
            bgColor: 'bg-amber-50 dark:bg-amber-900/20',
            iconColor: 'text-amber-500',
        },
        processing: {
            label: 'Sedang Diproses',
            variant: 'outline',
            icon: Clock,
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
            iconColor: 'text-blue-500',
        },
        paid: {
            label: 'Pembayaran Lunas',
            variant: 'default',
            icon: CheckCircle,
            bgColor: 'bg-green-50 dark:bg-green-900/20',
            iconColor: 'text-green-500',
        },
        failed: {
            label: 'Pembayaran Gagal',
            variant: 'destructive',
            icon: XCircle,
            bgColor: 'bg-red-50 dark:bg-red-900/20',
            iconColor: 'text-red-500',
        },
        expired: {
            label: 'Pembayaran Kedaluwarsa',
            variant: 'secondary',
            icon: AlertCircle,
            bgColor: 'bg-gray-50 dark:bg-gray-800',
            iconColor: 'text-gray-500',
        },
        refunded: {
            label: 'Dana Dikembalikan',
            variant: 'outline',
            icon: Receipt,
            bgColor: 'bg-purple-50 dark:bg-purple-900/20',
            iconColor: 'text-purple-500',
        },
        cancelled: {
            label: 'Dibatalkan',
            variant: 'secondary',
            icon: Ban,
            bgColor: 'bg-gray-50 dark:bg-gray-800',
            iconColor: 'text-gray-500',
        },
    };
    return configs[props.payment.status] || configs.pending;
});

// =============================================================================
// Actions
// =============================================================================

const handleCancel = async () => {
    const confirmed = await confirmation.confirm({
        title: 'Batalkan Pembayaran?',
        description: 'Pembayaran yang dibatalkan tidak dapat dikembalikan. Anda perlu membuat pembayaran baru jika ingin melanjutkan.',
        confirmText: 'Ya, Batalkan',
        variant: 'destructive',
    });

    if (confirmed) {
        router.post(paymentCancel.url(props.payment.id));
    }
};
</script>

<template>
    <Head :title="`Pembayaran ${payment.invoice_number}`" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Navbar />

        <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessages class="mb-6" />

            <!-- Back Button -->
            <div class="mb-6">
                <Button variant="ghost" size="sm" as-child>
                    <Link :href="paymentsIndex.url()">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Kembali ke Daftar Pembayaran
                    </Link>
                </Button>
            </div>

            <!-- Status Header Card -->
            <Card :class="['mb-6 overflow-hidden', statusConfig.bgColor]">
                <CardContent class="flex items-center gap-4 p-6">
                    <div class="rounded-full bg-white p-3 shadow-sm dark:bg-gray-800">
                        <component :is="statusConfig.icon" :class="['h-8 w-8', statusConfig.iconColor]" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ statusConfig.label }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ payment.invoice_number }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Details Card -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Detail Pembayaran</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Item -->
                    <div class="flex items-start gap-3">
                        <BookOpen class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Item</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ payment.payable_title }}
                            </p>
                            <p class="text-xs text-gray-500">{{ payment.payable_type }}</p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Amount -->
                    <div class="flex items-start gap-3">
                        <CreditCard class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ payment.formatted_amount }}
                            </p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Invoice Number -->
                    <div class="flex items-start gap-3">
                        <Hash class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nomor Invoice</p>
                            <p class="font-mono text-sm font-medium text-gray-900 dark:text-white">
                                {{ payment.invoice_number }}
                            </p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Gateway -->
                    <div class="flex items-start gap-3">
                        <Building class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Metode Pembayaran</p>
                            <p class="font-medium capitalize text-gray-900 dark:text-white">
                                {{ payment.gateway || 'Belum dipilih' }}
                            </p>
                            <p v-if="payment.gateway_transaction_id" class="font-mono text-xs text-gray-500">
                                ID: {{ payment.gateway_transaction_id }}
                            </p>
                        </div>
                    </div>

                    <Separator />

                    <!-- Dates -->
                    <div class="flex items-start gap-3">
                        <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-gray-400" />
                        <div class="space-y-2">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Dibuat</p>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ formatDateTime(payment.created_at) }}
                                </p>
                            </div>
                            <div v-if="payment.paid_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Pembayaran</p>
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    {{ formatDateTime(payment.paid_at) }}
                                </p>
                            </div>
                            <div v-if="payment.expires_at && isPayable">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Batas Pembayaran</p>
                                <p class="text-sm text-amber-600 dark:text-amber-400">
                                    {{ formatDateTime(payment.expires_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Actions Card -->
            <Card v-if="isPayable || canCancel || payment.enrollment_id">
                <CardHeader>
                    <CardTitle>Aksi</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <!-- Pay Now Button -->
                    <Button
                        v-if="isPayable && payment.payment_url"
                        class="w-full"
                        size="lg"
                        as-child
                    >
                        <a :href="payment.payment_url" target="_blank">
                            <ExternalLink class="mr-2 h-5 w-5" />
                            Lanjutkan Pembayaran
                        </a>
                    </Button>

                    <!-- Go to Course (if paid) -->
                    <Button
                        v-if="payment.is_paid && payment.enrollment_id"
                        class="w-full"
                        variant="outline"
                        as-child
                    >
                        <Link :href="courseShow.url(payment.payable_id)">
                            <BookOpen class="mr-2 h-5 w-5" />
                            Mulai Belajar
                        </Link>
                    </Button>

                    <!-- Cancel Button -->
                    <Button
                        v-if="canCancel"
                        variant="destructive"
                        class="w-full"
                        @click="handleCancel"
                    >
                        <Ban class="mr-2 h-5 w-5" />
                        Batalkan Pembayaran
                    </Button>
                </CardContent>
            </Card>

            <!-- Retry Card (for failed/expired) -->
            <Card v-if="payment.can_retry" class="border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20">
                <CardContent class="p-4">
                    <div class="flex items-start gap-3">
                        <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                        <div>
                            <p class="font-medium text-amber-800 dark:text-amber-200">
                                Pembayaran tidak berhasil
                            </p>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                Anda dapat membuat pembayaran baru untuk melanjutkan pendaftaran kursus ini.
                            </p>
                            <Button
                                class="mt-3"
                                size="sm"
                                as-child
                            >
                                <Link :href="courseShow.url(payment.payable_id)">
                                    Kembali ke Kursus
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </main>

        <Footer />

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :open="confirmation.isOpen.value"
            :title="confirmation.options.value.title"
            :description="confirmation.options.value.description"
            :confirm-text="confirmation.options.value.confirmText"
            :variant="confirmation.options.value.variant"
            @confirm="confirmation.handleConfirm"
            @cancel="confirmation.handleCancel"
        />
    </div>
</template>
