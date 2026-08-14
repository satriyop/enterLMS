<script setup lang="ts">
// =============================================================================
// Admin Category List Page
// Manage categories, view hierarchy, and delete unused categories
// =============================================================================

import { index, create, destroy, edit } from '@/actions/App/Http/Controllers/Admin/CategoryController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import Pagination from '@/components/crud/Pagination.vue';
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
import { truncate } from '@/lib/string';
import type { BreadcrumbItem, PaginationLink, Timestamps } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { useSearch } from '@/composables/ui/useSearch';
import { Plus, FolderTree, MoreVertical, Pencil, Trash2 } from 'lucide-vue-next';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface CategoryListItem extends Timestamps {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_category: {
        id: number;
        name: string;
    } | null;
    courses_count: number;
}

interface Props {
    categories: {
        data: CategoryListItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Kategori', href: index().url },
];

// =============================================================================
// State
// =============================================================================

const { query: search } = useSearch({
    url: () => index().url,
    initial: props.filters.search ?? '',
});

const confirmation = useConfirmation();

// =============================================================================
// Actions
// =============================================================================

const deleteCategory = async (category: CategoryListItem) => {
    if (category.courses_count > 0) {
        return;
    }

    const confirmed = await confirmation.confirm({
        title: 'Hapus Kategori',
        message: `Apakah Anda yakin ingin menghapus kategori "${category.name}"?`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(destroy(category.id).url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Manajemen Kategori" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Manajemen Kategori"
                description="Kelola kategori kursus dan struktur hierarki"
            >
                <template #actions>
                    <Link :href="create().url">
                        <Button size="lg" class="gap-2">
                            <Plus class="h-5 w-5" />
                            Tambah Kategori
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="w-full lg:w-80">
                    <SearchInput v-model="search" placeholder="Cari kategori..." />
                </div>
            </div>

            <EmptyState
                v-if="categories.data.length === 0"
                :icon="FolderTree"
                title="Belum ada kategori"
                description="Tambahkan kategori untuk mengorganisir kursus."
                action-label="Tambah Kategori"
                :action-href="create().url"
            />

            <template v-else>
                <!-- Category Table -->
                <div class="rounded-xl border bg-surface">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-surface-2/50">
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Nama
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Deskripsi
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Kategori Induk
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Jumlah Kursus
                                    </th>
                                    <th class="px-6 py-4 text-right text-sm font-medium text-muted-foreground">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="category in categories.data"
                                    :key="category.id"
                                    class="transition-colors hover:bg-surface-2/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-foreground">
                                            {{ category.name }}
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ category.slug }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-muted-foreground">
                                            {{ category.description ? truncate(category.description, 60) : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge v-if="category.parent_category" variant="outline">
                                            {{ category.parent_category.name }}
                                        </Badge>
                                        <span v-else class="text-sm text-muted-foreground">-</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-muted-foreground">
                                            {{ category.courses_count }} kursus
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <MoreVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-48">
                                                <DropdownMenuItem
                                                    :as="Link"
                                                    :href="edit(category.id).url"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    :class="category.courses_count > 0 ? 'opacity-50 cursor-not-allowed' : 'text-destructive focus:text-destructive'"
                                                    :disabled="category.courses_count > 0"
                                                    @click="deleteCategory(category)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    {{ category.courses_count > 0 ? 'Tidak dapat dihapus' : 'Hapus' }}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination
                    v-if="categories.last_page > 1"
                    :links="categories.links"
                    :current-page="categories.current_page"
                    :last-page="categories.last_page"
                    :from="categories.from"
                    :to="categories.to"
                    :total="categories.total"
                />
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
