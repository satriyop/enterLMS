<script setup lang="ts">
// =============================================================================
// Admin Edit Category Page
// Create and edit category with slug auto-generation
// =============================================================================

import { index, update, store } from '@/actions/App/Http/Controllers/Admin/CategoryController';
import PageHeader from '@/components/crud/PageHeader.vue';
import FormSection from '@/components/crud/FormSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { slugify } from '@/lib/string';
import type { BreadcrumbItem, Category, CategorySummary } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface Props {
    category: Category | null;
    parentCategories: CategorySummary[];
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const isEditing = props.category !== null;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Kategori', href: index().url },
    { title: isEditing ? 'Edit' : 'Tambah', href: '#' },
];

// =============================================================================
// Form State
// =============================================================================

const name = ref(props.category?.name ?? '');
const slug = ref(props.category?.slug ?? '');
const selectedParent = ref<string>(props.category?.parent_id?.toString() ?? '');

// =============================================================================
// Auto-generate slug from name (only on create)
// =============================================================================

watch(name, (value) => {
    if (!isEditing) {
        slug.value = slugify(value);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isEditing ? `Edit Kategori: ${category!.name}` : 'Tambah Kategori'" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                :title="isEditing ? 'Edit Kategori' : 'Tambah Kategori'"
                :description="isEditing ? category!.name : 'Buat kategori baru untuk mengorganisir kursus'"
                :back-href="index().url"
                back-label="Kembali ke Daftar Kategori"
            />

            <Form
                v-bind="isEditing ? update.form(category!.id) : store.form()"
                class="mx-auto w-full max-w-2xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <FormSection title="Informasi Kategori" description="Data kategori untuk mengorganisir kursus">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <Label for="name" class="text-sm font-medium">
                                Nama Kategori <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                v-model="name"
                                :default-value="category?.name"
                                placeholder="Contoh: Keamanan Siber"
                                class="h-11"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="slug" class="text-sm font-medium">
                                Slug <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="slug"
                                name="slug"
                                v-model="slug"
                                :default-value="category?.slug"
                                placeholder="keamanan-siber"
                                class="h-11"
                                :readonly="isEditing"
                                required
                            />
                            <InputError :message="errors.slug" />
                            <p class="text-sm text-muted-foreground">
                                {{ isEditing ? 'Slug tidak dapat diubah setelah dibuat.' : 'Otomatis dibuat dari nama kategori.' }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description" class="text-sm font-medium">
                                Deskripsi
                            </Label>
                            <Textarea
                                id="description"
                                name="description"
                                :default-value="category?.description ?? ''"
                                placeholder="Deskripsi kategori (opsional)"
                                rows="4"
                            />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="space-y-2">
                            <Label for="parent_id" class="text-sm font-medium">
                                Kategori Induk
                            </Label>
                            <input type="hidden" name="parent_id" :value="selectedParent || ''" />
                            <Select v-model="selectedParent">
                                <SelectTrigger class="h-11">
                                    <SelectValue placeholder="Pilih kategori induk (opsional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">
                                        Tanpa Kategori Induk
                                    </SelectItem>
                                    <SelectItem
                                        v-for="parent in parentCategories"
                                        :key="parent.id"
                                        :value="parent.id.toString()"
                                    >
                                        {{ parent.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.parent_id" />
                            <p class="text-sm text-muted-foreground">
                                Pilih kategori induk untuk membuat hierarki.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="order" class="text-sm font-medium">
                                Urutan
                            </Label>
                            <Input
                                id="order"
                                name="order"
                                type="number"
                                :default-value="category?.order ?? 0"
                                placeholder="0"
                                class="h-11"
                                min="0"
                            />
                            <InputError :message="errors.order" />
                            <p class="text-sm text-muted-foreground">
                                Urutan tampilan kategori (angka kecil muncul lebih dulu).
                            </p>
                        </div>
                    </div>
                </FormSection>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="index().url">
                        <Button type="button" variant="outline">
                            Batal
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Kategori') }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
