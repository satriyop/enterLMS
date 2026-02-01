<script setup lang="ts">
// =============================================================================
// Admin Edit Tag Page
// Create and edit tag with slug auto-generation
// =============================================================================

import { index, update, store } from '@/actions/App/Http/Controllers/Admin/TagController';
import PageHeader from '@/components/crud/PageHeader.vue';
import FormSection from '@/components/crud/FormSection.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { slugify } from '@/lib/string';
import type { BreadcrumbItem, Tag } from '@/types';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// =============================================================================
// Page-Specific Types
// =============================================================================

interface Props {
    tag: Tag | null;
}

// =============================================================================
// Component Setup
// =============================================================================

const props = defineProps<Props>();

const isEditing = props.tag !== null;

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Admin', href: '#' },
    { title: 'Tag', href: index().url },
    { title: isEditing ? 'Edit' : 'Tambah', href: '#' },
];

// =============================================================================
// Form State
// =============================================================================

const name = ref(props.tag?.name ?? '');
const slug = ref(props.tag?.slug ?? '');

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
        <Head :title="isEditing ? `Edit Tag: ${tag!.name}` : 'Tambah Tag'" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <PageHeader
                :title="isEditing ? 'Edit Tag' : 'Tambah Tag'"
                :description="isEditing ? tag!.name : 'Buat tag baru untuk kategorisasi kursus'"
                :back-href="index().url"
                back-label="Kembali ke Daftar Tag"
            />

            <Form
                v-bind="isEditing ? update.form(tag!.id) : store.form()"
                class="mx-auto w-full max-w-2xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <FormSection title="Informasi Tag" description="Data tag untuk kategorisasi kursus">
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <Label for="name" class="text-sm font-medium">
                                Nama Tag <span class="text-destructive">*</span>
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                v-model="name"
                                :default-value="tag?.name"
                                placeholder="Contoh: OJK Regulation"
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
                                :default-value="tag?.slug"
                                placeholder="ojk-regulation"
                                class="h-11"
                                :readonly="isEditing"
                                required
                            />
                            <InputError :message="errors.slug" />
                            <p class="text-sm text-muted-foreground">
                                {{ isEditing ? 'Slug tidak dapat diubah setelah dibuat.' : 'Otomatis dibuat dari nama tag.' }}
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
                        {{ processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Buat Tag') }}
                    </Button>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
