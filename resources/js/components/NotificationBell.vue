<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const unreadCount = computed(() => page.props.unreadNotificationsCount as number);
</script>

<template>
    <Link href="/notifications">
        <Button variant="ghost" size="icon" class="relative h-9 w-9">
            <Bell class="h-5 w-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
            <span class="sr-only">Notifikasi</span>
        </Button>
    </Link>
</template>
