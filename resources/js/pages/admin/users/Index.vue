<script setup lang="ts">
// =============================================================================
// Admin User List Page
// Manage users, roles, and view activity
// =============================================================================

import { index, create, destroy, edit } from '@/actions/App/Http/Controllers/Admin/UserController';
import PageHeader from '@/components/crud/PageHeader.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import FilterTabs from '@/components/crud/FilterTabs.vue';
import SearchInput from '@/components/crud/SearchInput.vue';
import Pagination from '@/components/crud/Pagination.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import { roleLabel, roleBadgeVariant } from '@/lib/formatters';
import { getInitials } from '@/lib/string';
import type { BreadcrumbItem, PaginationLink, UserRole } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { useConfirmation } from '@/composables/ui/useConfirmation';
import { useSearch } from '@/composables/ui/useSearch';
import { Plus, Users, MoreVertical, Pencil, Trash2, BookOpen, GraduationCap } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface UserListItem {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    created_at: string;
    courses_count: number;
    enrollments_count: number;
}

interface Props {
    users: {
        data: UserListItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Pengguna', href: index().url },
];

// =============================================================================
// State
// =============================================================================

const role = ref(props.filters.role ?? '');

const { query: search } = useSearch({
    url: () => index().url,
    initial: props.filters.search ?? '',
    extraParams: () => ({ role: role.value || undefined }),
});

const confirmation = useConfirmation();

// =============================================================================
// Computed
// =============================================================================

const roleTabs = computed(() => [
    { value: '', label: 'Semua', count: undefined },
    { value: 'learner', label: 'Peserta Didik' },
    { value: 'content_manager', label: 'Pengelola Konten' },
    { value: 'trainer', label: 'Pelatih' },
    { value: 'lms_admin', label: 'Admin LMS' },
]);

// =============================================================================
// Helpers
// =============================================================================

const getRoleBadge = (userRole: UserRole) => ({
    label: roleLabel(userRole),
    variant: roleBadgeVariant(userRole),
});

// =============================================================================
// Watchers
// =============================================================================

watch(role, (value) => {
    router.get(index().url, { search: search.value, role: value }, { preserveState: true, replace: true });
});

// =============================================================================
// Actions
// =============================================================================

const deleteUser = async (user: UserListItem) => {
    const confirmed = await confirmation.confirm({
        title: 'Hapus Pengguna',
        message: `Apakah Anda yakin ingin menghapus pengguna "${user.name}"?`,
        destructive: true,
    });
    if (confirmed) {
        router.delete(destroy(user.id).url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Manajemen Pengguna" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title="Manajemen Pengguna"
                description="Kelola pengguna, peran, dan lihat aktivitas"
            >
                <template #actions>
                    <Link :href="create().url">
                        <Button size="lg" class="gap-2">
                            <Plus class="h-5 w-5" />
                            Tambah Pengguna
                        </Button>
                    </Link>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <FilterTabs v-model="role" :tabs="roleTabs" />

                <div class="w-full lg:w-80">
                    <SearchInput v-model="search" placeholder="Cari nama atau email..." />
                </div>
            </div>

            <EmptyState
                v-if="users.data.length === 0"
                :icon="Users"
                title="Belum ada pengguna"
                description="Tambahkan pengguna baru untuk mulai mengelola akses LMS."
                action-label="Tambah Pengguna"
                :action-href="create().url"
            />

            <template v-else>
                <!-- User Table -->
                <div class="rounded-xl border bg-card">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Pengguna
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Peran
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Aktivitas
                                    </th>
                                    <th class="px-6 py-4 text-left text-sm font-medium text-muted-foreground">
                                        Terdaftar
                                    </th>
                                    <th class="px-6 py-4 text-right text-sm font-medium text-muted-foreground">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="transition-colors hover:bg-muted/50"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-10 w-10">
                                                <AvatarFallback class="bg-primary/10 text-primary">
                                                    {{ getInitials(user.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <div class="font-medium text-foreground">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-sm text-muted-foreground">
                                                    {{ user.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <Badge :variant="getRoleBadge(user.role).variant">
                                            {{ getRoleBadge(user.role).label }}
                                        </Badge>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                            <span class="flex items-center gap-1.5">
                                                <BookOpen class="h-4 w-4" />
                                                {{ user.courses_count }} kursus
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <GraduationCap class="h-4 w-4" />
                                                {{ user.enrollments_count }} pendaftaran
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-muted-foreground">
                                        {{ formatDate(user.created_at, 'short') }}
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
                                                    :href="edit(user.id).url"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="text-destructive focus:text-destructive"
                                                    @click="deleteUser(user)"
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
                    v-if="users.last_page > 1"
                    :links="users.links"
                    :current-page="users.current_page"
                    :last-page="users.last_page"
                    :from="users.from"
                    :to="users.to"
                    :total="users.total"
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
