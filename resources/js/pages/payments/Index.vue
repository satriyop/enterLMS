<script setup lang="ts">
// =============================================================================
// User Payments List Page
// Shows user's payment history with status and actions
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import Pagination from '@/components/crud/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/vue3';
import {
    CreditCard,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    ExternalLink,
    Eye,
    Calendar,
    Receipt,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { formatDate } from '@/lib/date';
import type { PaginationLink } from '@/types';
import { show as paymentShow } from '@/actions/App/Http/Controllers/PaymentController';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';

// =============================================================================
// Types
// =============================================================================

type PaymentStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'expired' | 'refunded' | 'cancelled';

interface PaymentItem {
    id: number;
    invoice_number: string;
    payable_type: string;
    payable_title: string;
    amount: number;
    formatted_amount: string;
    currency: string;
    status: PaymentStatus;
    gateway: string;
    payment_url: string | null;
    paid_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface Props {
    payments: {
        data: PaymentItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
}

const props = defineProps<Props>();

// =============================================================================
// Computed
// =============================================================================

const hasPayments = computed(() => props.payments.data.length > 0);

// =============================================================================
// Helpers
// =============================================================================

const getStatusConfig = (status: PaymentStatus) => {
    const configs: Record<PaymentStatus, { label: string; variant: 'default' | 'secondary' | 'destructive' | 'outline'; icon: typeof Clock }> = {
        pending: { label: 'Menunggu', variant: 'secondary', icon: Clock },
        processing: { label: 'Diproses', variant: 'outline', icon: Clock },
        paid: { label: 'Lunas', variant: 'default', icon: CheckCircle },
        failed: { label: 'Gagal', variant: 'destructive', icon: XCircle },
        expired: { label: 'Kedaluwarsa', variant: 'secondary', icon: AlertCircle },
        refunded: { label: 'Dikembalikan', variant: 'outline', icon: Receipt },
        cancelled: { label: 'Dibatalkan', variant: 'secondary', icon: XCircle },
    };
    return configs[status] || configs.pending;
};

const isPayable = (status: PaymentStatus) => {
    return status === 'pending' || status === 'processing';
};

const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
    }).format(amount);
};
</script>

<template>
    <Head title="Riwayat Pembayaran" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Navbar />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessages class="mb-6" />

            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                        <CreditCard class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Riwayat Pembayaran
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Daftar pembayaran kursus Anda
                        </p>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="!hasPayments"
                title="Belum ada pembayaran"
                description="Riwayat pembayaran Anda akan muncul di sini."
                :icon="CreditCard"
            >
                <template #action>
                    <Button as-child>
                        <Link :href="coursesIndex.url()">Jelajahi Kursus</Link>
                    </Button>
                </template>
            </EmptyState>

            <!-- Payments List -->
            <div v-else class="space-y-4">
                <Card
                    v-for="payment in payments.data"
                    :key="payment.id"
                    class="overflow-hidden transition-shadow hover:shadow-md"
                >
                    <CardContent class="p-0">
                        <div class="flex flex-col sm:flex-row">
                            <!-- Status Indicator -->
                            <div
                                :class="[
                                    'flex items-center justify-center p-4 sm:w-24',
                                    payment.status === 'paid' ? 'bg-green-50 dark:bg-green-900/20' :
                                    payment.status === 'failed' ? 'bg-red-50 dark:bg-red-900/20' :
                                    'bg-gray-50 dark:bg-gray-800',
                                ]"
                            >
                                <component
                                    :is="getStatusConfig(payment.status).icon"
                                    :class="[
                                        'h-8 w-8',
                                        payment.status === 'paid' ? 'text-green-500' :
                                        payment.status === 'failed' ? 'text-red-500' :
                                        'text-gray-400',
                                    ]"
                                />
                            </div>

                            <!-- Payment Details -->
                            <div class="flex flex-1 flex-col justify-between p-4 sm:flex-row sm:items-center">
                                <div class="mb-3 sm:mb-0">
                                    <!-- Title & Invoice -->
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ payment.payable_title }}
                                    </h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-mono text-xs">{{ payment.invoice_number }}</span>
                                        <span>•</span>
                                        <span class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(payment.created_at) }}
                                        </span>
                                    </div>

                                    <!-- Status Badge -->
                                    <div class="mt-2">
                                        <Badge :variant="getStatusConfig(payment.status).variant">
                                            {{ getStatusConfig(payment.status).label }}
                                        </Badge>
                                    </div>
                                </div>

                                <!-- Amount & Actions -->
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ payment.formatted_amount }}
                                        </p>
                                        <p v-if="payment.paid_at" class="text-xs text-green-600 dark:text-green-400">
                                            Dibayar {{ formatDate(payment.paid_at) }}
                                        </p>
                                        <p v-else-if="payment.expires_at && isPayable(payment.status)" class="text-xs text-amber-600 dark:text-amber-400">
                                            Exp: {{ formatDate(payment.expires_at) }}
                                        </p>
                                    </div>

                                    <div class="flex gap-2">
                                        <!-- Pay Now Button -->
                                        <Button
                                            v-if="isPayable(payment.status) && payment.payment_url"
                                            size="sm"
                                            as-child
                                        >
                                            <a :href="payment.payment_url" target="_blank">
                                                <ExternalLink class="mr-1.5 h-4 w-4" />
                                                Bayar
                                            </a>
                                        </Button>

                                        <!-- View Details -->
                                        <Button variant="outline" size="sm" as-child>
                                            <Link :href="paymentShow.url(payment.id)">
                                                <Eye class="mr-1.5 h-4 w-4" />
                                                Detail
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Pagination -->
                <Pagination
                    v-if="payments.last_page > 1"
                    :links="payments.links"
                    :from="payments.from"
                    :to="payments.to"
                    :total="payments.total"
                />
            </div>
        </main>

        <Footer />
    </div>
</template>
