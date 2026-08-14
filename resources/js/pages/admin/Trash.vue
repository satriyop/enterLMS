<script setup lang="ts">
// =============================================================================
// Admin Trash Page
// Centralized view and management of all soft-deleted records
// =============================================================================

import { index, restore, forceDelete } from '@/actions/App/Http/Controllers/Admin/TrashController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import FilterTabs from '@/components/crud/FilterTabs.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate } from '@/lib/date';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { Trash2, RotateCcw, MoreVertical } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface TrashedItem {
    id: number;
    title: string;
    detail: string;
    deleted_at: string;
}

interface TrashedGroup {
    type: string;
    label: string;
    items: TrashedItem[];
}

interface SummaryEntry {
    label: string;
    count: number;
}

interface Props {
    groups: TrashedGroup[];
    filters: {
        type?: string;
    };
    summary: Record<string, SummaryEntry>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Tempat Sampah', href: index().url },
];

// =============================================================================
// State
// =============================================================================

const type = ref(props.filters.type ?? '');
const confirmation = useConfirmation();

// =============================================================================
// Computed
// =============================================================================

const totalTrashed = computed(() =>
    Object.values(props.summary).reduce((sum, entry) => sum + entry.count, 0)
);

const typeTabs = computed(() => {
    const tabs = [{ value: '', label: 'Semua', count: totalTrashed.value }];
    for (const [key, entry] of Object.entries(props.summary)) {
        if (entry.count > 0) {
            tabs.push({ value: key, label: entry.label, count: entry.count });
        }
    }
    return tabs;
});

// =============================================================================
// Watchers
// =============================================================================

watch(type, (value) => {
    router.get(index().url, { type: value || undefined }, { preserveState: true, replace: true });
});

// =============================================================================
// Actions
// =============================================================================

const restoreItem = async (group: TrashedGroup, item: TrashedItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Pulihkan Item',
        message: `Apakah Anda yakin ingin memulihkan "${item.title}"?`,
    });
    if (confirmed) {
        router.post(restore({ type: group.type, id: item.id }).url);
    }
};

const deleteItem = async (group: TrashedGroup, item: TrashedItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Hapus Permanen',
        message: `Apakah Anda yakin ingin menghapus "${item.title}" secara permanen? Tindakan ini tidak dapat dibatalkan.`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(forceDelete({ type: group.type, id: item.id }).url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tempat Sampah" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Tempat Sampah"
                description="Kelola item yang telah dihapus. Item akan dihapus permanen setelah 30 hari."
            />

            <FilterTabs v-model="type" :tabs="typeTabs" />

            <EmptyState
                v-if="groups.length === 0"
                :icon="Trash2"
                title="Tempat sampah kosong"
                description="Tidak ada item yang dihapus saat ini."
            />

            <template v-else>
                <div v-for="group in groups" :key="group.type" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-semibold text-foreground">{{ group.label }}</h3>
                        <Badge variant="secondary" class="text-xs">{{ group.items.length }}</Badge>
                    </div>

                    <div class="rounded-xl border bg-surface">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b bg-surface-2/50">
                                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Nama
                                        </th>
                                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Detail
                                        </th>
                                        <th class="px-6 py-3 text-left text-sm font-medium text-muted-foreground">
                                            Dihapus
                                        </th>
                                        <th class="px-6 py-3 text-right text-sm font-medium text-muted-foreground">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr
                                        v-for="item in group.items"
                                        :key="item.id"
                                        class="transition-colors hover:bg-surface-2/50"
                                    >
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-foreground">{{ item.title }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ item.detail }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-muted-foreground">
                                            {{ formatDate(item.deleted_at, 'short') }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="ghost" size="icon" class="h-8 w-8">
                                                        <MoreVertical class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" class="w-48">
                                                    <DropdownMenuItem @click="restoreItem(group, item)">
                                                        <RotateCcw class="mr-2 h-4 w-4" />
                                                        Pulihkan
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        class="text-destructive focus:text-destructive"
                                                        @click="deleteItem(group, item)"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        Hapus Permanen
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <ConfirmationDialog
            :open="confirmation.isOpen.value"
            :title="confirmation.title.value"
            :message="confirmation.message.value"
            :confirm-label="confirmation.confirmLabel.value"
            :cancel-label="confirmation.cancelLabel.value"
            :destructive="confirmation.isDestructive.value"
            @confirm="confirmation.handleConfirm"
            @cancel="confirmation.handleCancel"
        />
    </AppLayout>
</template>
