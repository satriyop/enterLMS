<script setup lang="ts">
// =============================================================================
// Admin Tag List Page
// Manage tags for course categorization
// =============================================================================

import TagController from '@/actions/App/Http/Controllers/Admin/TagController';
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
import type { BreadcrumbItem, PaginationLink, Timestamps } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { useSearch } from '@/composables/ui/useSearch';
import { Plus, TagIcon, MoreVertical, Pencil, Trash2 } from 'lucide-vue-next';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface TagListItem extends Timestamps {
    id: number;
    name: string;
    slug: string;
    courses_count: number;
}

interface Props {
    tags: {
        data: TagListItem[];
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
    { title: 'Tag', href: TagController.index().url },
];

// =============================================================================
// State
// =============================================================================

const { query: search } = useSearch({
    url: () => TagController.index().url,
    initial: props.filters.search ?? '',
});

const confirmation = useConfirmation();

// =============================================================================
// Actions
// =============================================================================

const deleteTag = async (tag: TagListItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Hapus Tag',
        message: `Apakah Anda yakin ingin menghapus tag "${tag.name}"?`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(TagController.destroy(tag.id).url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Manajemen Tag" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Manajemen Tag"
                description="Kelola tag untuk kategorisasi kursus"
            >
                <template #actions>
                    <Link :href="TagController.create().url">
                        <Button size="lg" class="gap-2">
                            <Plus class="h-5 w-5" />
                            Tambah Tag
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="w-full lg:w-80">
                    <SearchInput v-model="search" placeholder="Cari tag..." />
                </div>
            </div>

            <EmptyState
                v-if="tags.data.length === 0"
                :icon="TagIcon"
                title="Belum ada tag"
                description="Tambahkan tag untuk kategorisasi kursus."
                action-label="Tambah Tag"
                :action-href="TagController.create().url"
            />

            <template v-else>
                <!-- Tag Table -->
                <div class="rounded-xl border bg-card">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Nama
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Slug
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
                                    v-for="tag in tags.data"
                                    :key="tag.id"
                                    class="transition-colors hover:bg-muted/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <Badge variant="secondary">
                                                {{ tag.name }}
                                            </Badge>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-muted-foreground">
                                            {{ tag.slug }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-muted-foreground">
                                            {{ tag.courses_count }} kursus
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
                                                    :href="TagController.edit(tag.id).url"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="text-destructive focus:text-destructive"
                                                    @click="deleteTag(tag)"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Hapus
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
                    v-if="tags.last_page > 1"
                    :links="tags.links"
                    :current-page="tags.current_page"
                    :last-page="tags.last_page"
                    :from="tags.from"
                    :to="tags.to"
                    :total="tags.total"
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
