<script setup lang="ts">
// =============================================================================
// Notifications Index Page
// View all user notifications with read/unread state
// =============================================================================

import Navbar from '@/components/home/Navbar.vue';
import Footer from '@/components/home/Footer.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BellOff,
    Check,
    CheckCheck,
    BookOpen,
    GraduationCap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { index as coursesIndex } from '@/actions/App/Http/Controllers/CourseController';
import type { PaginationLink } from '@/types';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface NotificationData {
    type: string;
    course_id?: number;
    course_title?: string;
    enrollment_id?: number;
    message: string;
}

interface NotificationItem {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
}

interface PaginatedNotifications {
    data: NotificationItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface Props {
    notifications: PaginatedNotifications;
}

const props = defineProps<Props>();

const page = usePage();
const appName = computed(() => page.props.name || 'E-Learning');

// =============================================================================
// Notification Helpers
// =============================================================================

const formatRelativeTime = (dateString: string): string => {
    const diff = Date.now() - new Date(dateString).getTime();
    const minutes = Math.floor(diff / 60000);

    if (minutes < 1) return 'Baru saja';
    if (minutes < 60) return `${minutes} menit lalu`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} jam lalu`;

    const days = Math.floor(hours / 24);
    if (days < 7) return `${days} hari lalu`;
    if (days < 30) return `${Math.floor(days / 7)} minggu lalu`;

    return `${Math.floor(days / 30)} bulan lalu`;
};

const getNotificationIcon = (type: string) => {
    if (type === 'enrollment.welcome') {
        return { component: BookOpen, color: 'text-info' };
    }
    if (type === 'enrollment.completed') {
        return { component: GraduationCap, color: 'text-gold' };
    }
    return { component: Bell, color: 'text-muted-foreground' };
};

const markAsRead = (notification: NotificationItem) => {
    if (notification.read_at) {
        // Already read, just navigate if there's a course
        if (notification.data.course_id) {
            router.visit(`/courses/${notification.data.course_id}`);
        }
        return;
    }

    // Mark as read and navigate
    router.patch(
        `/notifications/${notification.id}/read`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                if (notification.data.course_id) {
                    router.visit(`/courses/${notification.data.course_id}`);
                }
            },
        },
    );
};

const markAllAsRead = () => {
    router.post('/notifications/mark-all-read', {}, {
        preserveScroll: true,
    });
};

const hasUnreadNotifications = computed(() => {
    return props.notifications.data.some((n) => !n.read_at);
});
</script>

<template>
    <Head title="Notifikasi" />

    <div class="min-h-screen">
        <Navbar :app-name="appName" />

        <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <FlashMessages />

            <!-- Header -->
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-3xl font-bold flex items-center gap-2">
                        <Bell class="h-8 w-8" />
                        Notifikasi
                    </h1>
                    <p class="mt-2 text-muted-foreground">
                        {{ notifications.total }} notifikasi
                    </p>
                </div>
                <Button
                    v-if="hasUnreadNotifications"
                    variant="outline"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="mr-2 h-4 w-4" />
                    Tandai Semua Dibaca
                </Button>
            </div>

            <!-- Notification List -->
            <div v-if="notifications.data.length > 0" class="space-y-3">
                <Card
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="cursor-pointer transition-colors hover:bg-surface-2/50"
                    :class="{ 'border-l-4 border-l-info bg-info-soft/50': !notification.read_at, }"
                    @click="markAsRead(notification)"
                >
                    <CardContent class="p-4">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-surface-2"
                                :class="{ 'bg-info-soft': notification.data.type === 'enrollment.welcome', 'bg-gold-soft': notification.data.type === 'enrollment.completed', }"
                            >
                                <component
                                    :is="getNotificationIcon(notification.data.type).component"
                                    class="h-5 w-5"
                                    :class="getNotificationIcon(notification.data.type).color"
                                />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm"
                                    :class="{ 'font-semibold text-foreground': !notification.read_at, 'text-muted-foreground': notification.read_at, }"
                                >
                                    {{ notification.data.message }}
                                </p>
                                <p
                                    class="mt-1 text-xs"
                                    :class="{ 'text-muted-foreground': !notification.read_at, 'text-muted-foreground/70': notification.read_at, }"
                                >
                                    {{ formatRelativeTime(notification.created_at) }}
                                </p>
                            </div>

                            <!-- Read indicator -->
                            <div class="shrink-0">
                                <Badge
                                    v-if="!notification.read_at"
                                    variant="default"
                                    class="bg-info"
                                >
                                    Baru
                                </Badge>
                                <Check v-else class="h-5 w-5 text-muted-foreground/50" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else
                :icon="BellOff"
                title="Belum ada notifikasi"
                description="Notifikasi muncul saat Anda mendaftar kursus, menyelesaikan pembelajaran, atau menerima undangan."
            >
                <template #action>
                    <Button as-child variant="outline">
                        <Link :href="coursesIndex().url">Jelajahi Kursus</Link>
                    </Button>
                </template>
            </EmptyState>

            <!-- Pagination -->
            <div v-if="notifications.last_page > 1" class="mt-8 flex justify-center gap-2">
                <template v-for="link in notifications.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border px-3 py-2 text-sm transition-colors hover:bg-surface-2"
                        :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active, }"
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="rounded-md border px-3 py-2 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </main>

        <Footer :app-name="appName" />
    </div>
</template>
