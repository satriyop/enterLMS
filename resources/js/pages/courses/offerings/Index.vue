<script setup lang="ts">
import { index as courseShow } from '@/actions/App/Http/Controllers/CourseController';
import { bulkEnroll } from '@/actions/App/Http/Controllers/EnrollmentController';
import {
    destroy,
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/CourseOfferingController';
import LearnerSearchCombobox from '@/components/courses/LearnerSearchCombobox.vue';
import EmptyState from '@/components/crud/EmptyState.vue';
import FormSection from '@/components/crud/FormSection.vue';
import PageHeader from '@/components/crud/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, router } from '@inertiajs/vue3';
import { Layers, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OfferingItem {
    id: number;
    name: string;
    code: string;
    starts_at: string | null;
    ends_at: string | null;
    capacity: number | null;
    is_default: boolean;
    is_open: boolean;
    enrollments_count?: number;
    facilitator?: { id: number; name: string; email: string } | null;
}

interface Props {
    course: { id: number; title: string };
    offerings: OfferingItem[];
    grantOfferingIds: number[];
    label: string;
    can: { create: boolean; grant: boolean };
}

const props = defineProps<Props>();

const grantUserId = ref<number | null>(null);
const grantOfferingId = ref<number | null>(null);
const grantProcessing = ref(false);
const grantErrors = ref<Record<string, string>>({});
const rosterFile = ref<File | null>(null);
const rosterProcessing = ref(false);

const onRosterFile = (event: Event) => {
    const input = event.target as HTMLInputElement;
    rosterFile.value = input.files?.[0] ?? null;
};

const submitRoster = () => {
    if (!rosterFile.value) {
        grantErrors.value = { file: 'Silakan pilih file CSV terlebih dahulu' };
        return;
    }

    rosterProcessing.value = true;
    grantErrors.value = {};

    const formData = new FormData();
    formData.append('file', rosterFile.value);

    router.post(bulkEnroll.url(props.course.id), formData, {
        preserveScroll: true,
        onError: (formErrors) => {
            grantErrors.value = formErrors as Record<string, string>;
        },
        onFinish: () => {
            rosterProcessing.value = false;
        },
    });
};

const grantableOfferings = computed(() => {
    const allowed = new Set(props.grantOfferingIds ?? []);

    return props.offerings.filter((offering) => allowed.has(offering.id));
});

const submitGrant = () => {
    if (!grantUserId.value) {
        grantErrors.value = { user_ids: 'Silakan pilih peserta terlebih dahulu' };
        return;
    }

    const offeringId =
        grantOfferingId.value ?? grantableOfferings.value[0]?.id ?? null;

    if (!offeringId) {
        grantErrors.value = { offering_id: `Pilih ${props.label} terlebih dahulu` };
        return;
    }

    grantProcessing.value = true;
    grantErrors.value = {};

    router.post(
        bulkEnroll.url(props.course.id),
        {
            user_ids: [grantUserId.value],
            offering_id: offeringId,
        },
        {
            preserveScroll: true,
            onError: (formErrors) => {
                grantErrors.value = formErrors as Record<string, string>;
            },
            onFinish: () => {
                grantProcessing.value = false;
            },
        },
    );
};

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Kursus', href: courseShow.url(props.course.id) },
    { title: props.label, href: index.url(props.course.id) },
];

const datetimeLocal = (value: string | null): string => {
    if (!value) {
        return '';
    }

    return value.slice(0, 16);
};

const deleteOffering = (offering: OfferingItem) => {
    if (offering.is_default) {
        return;
    }

    if (!confirm(`Hapus ${props.label} "${offering.name}"?`)) {
        return;
    }

    router.delete(
        destroy.url({ course: props.course.id, offering: offering.id }),
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`${label} — ${course.title}`" />

        <div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            <PageHeader
                :title="label"
                :description="`Run dari kursus ${course.title}`"
                :back-href="courseShow.url(course.id)"
                back-label="Kembali ke kursus"
            />

            <FormSection
                v-if="can.grant && grantableOfferings.length > 0"
                :title="`Daftarkan ke ${label}`"
                :description="`Grant Enrollment ke ${label} bernama. Bukan ke kursus secara umum.`"
            >
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitGrant">
                    <div class="sm:col-span-2">
                        <LearnerSearchCombobox
                            v-model="grantUserId"
                            :course-id="course.id"
                            label="Peserta"
                        />
                        <InputError :message="grantErrors.user_ids" />
                    </div>
                    <div class="sm:col-span-2">
                        <Label for="grant_offering_id">{{ label }}</Label>
                        <select
                            id="grant_offering_id"
                            v-model="grantOfferingId"
                            class="flex h-10 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        >
                            <option
                                v-for="offering in grantableOfferings"
                                :key="offering.id"
                                :value="offering.id"
                            >
                                {{ offering.name }}
                                <template v-if="offering.code">
                                    ({{ offering.code }})
                                </template>
                            </option>
                        </select>
                        <InputError :message="grantErrors.offering_id" />
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <Button type="submit" :disabled="grantProcessing">
                            {{
                                grantProcessing
                                    ? 'Mendaftarkan...'
                                    : 'Daftarkan'
                            }}
                        </Button>
                    </div>
                </form>

                <form class="mt-6 grid gap-4 border-t border-border pt-6" @submit.prevent="submitRoster">
                    <div>
                        <Label for="roster_csv">Roster CSV (nim, offering_code)</Label>
                        <Input
                            id="roster_csv"
                            type="file"
                            accept=".csv,text/csv"
                            class="mt-1"
                            @change="onRosterFile"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Kolom wajib: nim, offering_code. NIM yang tidak
                            ditemukan atau kode default saat {{ label }} bernama
                            sudah ada menjadi baris error.
                        </p>
                        <InputError :message="grantErrors.file" />
                    </div>
                    <div class="flex justify-end">
                        <Button type="submit" variant="outline" :disabled="rosterProcessing">
                            {{
                                rosterProcessing
                                    ? 'Mengunggah...'
                                    : 'Unggah roster'
                            }}
                        </Button>
                    </div>
                </form>
            </FormSection>

            <FormSection v-if="can.create" :title="`Buat ${label}`">
                <Form
                    v-bind="store.form(course.id)"
                    class="grid gap-4 sm:grid-cols-2"
                    #default="{ errors, processing }"
                >
                    <div class="sm:col-span-2">
                        <Label for="name">Nama</Label>
                        <Input id="name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>
                    <div>
                        <Label for="code">Kode (opsional)</Label>
                        <Input id="code" name="code" />
                        <InputError :message="errors.code" />
                    </div>
                    <div>
                        <Label for="capacity">Kapasitas (opsional)</Label>
                        <Input
                            id="capacity"
                            name="capacity"
                            type="number"
                            min="1"
                        />
                        <InputError :message="errors.capacity" />
                    </div>
                    <div>
                        <Label for="starts_at">Mulai</Label>
                        <Input
                            id="starts_at"
                            name="starts_at"
                            type="datetime-local"
                        />
                        <InputError :message="errors.starts_at" />
                    </div>
                    <div>
                        <Label for="ends_at">Selesai</Label>
                        <Input
                            id="ends_at"
                            name="ends_at"
                            type="datetime-local"
                        />
                        <InputError :message="errors.ends_at" />
                    </div>
                    <div class="sm:col-span-2">
                        <Label for="facilitator_email"
                            >Email facilitator (opsional)</Label
                        >
                        <Input
                            id="facilitator_email"
                            name="facilitator_email"
                            type="email"
                        />
                        <InputError :message="errors.facilitator_email" />
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <Button type="submit" :disabled="processing">
                            {{
                                processing ? 'Menyimpan...' : `Tambah ${label}`
                            }}
                        </Button>
                    </div>
                </Form>
            </FormSection>

            <div v-if="offerings.length > 0" class="space-y-4">
                <Form
                    v-for="offering in offerings"
                    :key="offering.id"
                    v-bind="
                        update.form({
                            course: course.id,
                            offering: offering.id,
                        })
                    "
                    #default="{ errors, processing }"
                >
                    <FormSection :title="offering.name">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <Label :for="`name-${offering.id}`">Nama</Label>
                                <Input
                                    :id="`name-${offering.id}`"
                                    name="name"
                                    :default-value="offering.name"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div>
                                <Label>Kode</Label>
                                <Input :value="offering.code" disabled />
                            </div>
                            <div>
                                <Label :for="`capacity-${offering.id}`"
                                    >Kapasitas</Label
                                >
                                <Input
                                    :id="`capacity-${offering.id}`"
                                    name="capacity"
                                    type="number"
                                    min="1"
                                    :default-value="offering.capacity ?? ''"
                                />
                                <InputError :message="errors.capacity" />
                            </div>
                            <div>
                                <Label :for="`starts-${offering.id}`"
                                    >Mulai</Label
                                >
                                <Input
                                    :id="`starts-${offering.id}`"
                                    name="starts_at"
                                    type="datetime-local"
                                    :default-value="
                                        datetimeLocal(offering.starts_at)
                                    "
                                />
                            </div>
                            <div>
                                <Label :for="`ends-${offering.id}`"
                                    >Selesai</Label
                                >
                                <Input
                                    :id="`ends-${offering.id}`"
                                    name="ends_at"
                                    type="datetime-local"
                                    :default-value="
                                        datetimeLocal(offering.ends_at)
                                    "
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <Label :for="`facilitator-${offering.id}`"
                                    >Email facilitator</Label
                                >
                                <Input
                                    :id="`facilitator-${offering.id}`"
                                    name="facilitator_email"
                                    type="email"
                                    :default-value="
                                        offering.facilitator?.email ?? ''
                                    "
                                />
                                <InputError
                                    :message="errors.facilitator_email"
                                />
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-muted-foreground">
                            {{ offering.enrollments_count ?? 0 }} pendaftaran
                            <span v-if="offering.is_default"> · default</span>
                            <span v-if="!offering.is_open"> · tertutup</span>
                            <span v-if="offering.facilitator">
                                · {{ offering.facilitator.name }}</span
                            >
                        </p>
                        <div class="mt-4 flex justify-end gap-2">
                            <Button
                                v-if="!offering.is_default"
                                type="button"
                                variant="outline"
                                @click="deleteOffering(offering)"
                            >
                                <Trash2 class="size-4" />
                                Hapus
                            </Button>
                            <Button type="submit" :disabled="processing">
                                Simpan
                            </Button>
                        </div>
                    </FormSection>
                </Form>
            </div>

            <EmptyState
                v-else
                :icon="Layers"
                :title="`Belum ada ${label}`"
                description="Buat run pertama untuk kursus ini."
            />
        </div>
    </AppLayout>
</template>
