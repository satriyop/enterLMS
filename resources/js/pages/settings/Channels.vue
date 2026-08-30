<script setup lang="ts">
import {
    destroy,
    edit,
    update,
} from '@/actions/App/Http/Controllers/Settings/ChannelIdentityController';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';

interface Props {
    whatsapp: string | null;
    telegram: string | null;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'WhatsApp & Telegram',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="WhatsApp & Telegram" />

        <SettingsLayout>
            <div class="space-y-10">
                <HeadingSmall
                    title="Tautkan WhatsApp dan Telegram"
                    description="Tutor di overlay, WhatsApp, dan Telegram adalah dosen yang sama. Tautkan nomor atau ID agar pesan dari kanal itu tercatat sebagai kamu."
                />

                <div class="space-y-6">
                    <Form
                        :action="update.url('whatsapp')"
                        method="put"
                        class="space-y-4"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="whatsapp">Nomor WhatsApp</Label>
                            <Input
                                id="whatsapp"
                                name="identifier"
                                type="tel"
                                inputmode="tel"
                                class="mt-1 block w-full"
                                :default-value="whatsapp ?? ''"
                                autocomplete="tel"
                                placeholder="0812 3456 7890"
                            />
                            <p class="text-sm text-muted-foreground">
                                Format 08… atau 62…. Satu nomor hanya untuk satu
                                akun.
                            </p>
                            <InputError :message="errors.identifier" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button type="submit" :disabled="processing">
                                {{
                                    processing
                                        ? 'Menyimpan…'
                                        : 'Simpan WhatsApp'
                                }}
                            </Button>
                            <p
                                v-if="recentlySuccessful"
                                class="text-sm text-muted-foreground"
                            >
                                Tersimpan.
                            </p>
                        </div>
                    </Form>

                    <Form
                        v-if="whatsapp"
                        :action="destroy.url('whatsapp')"
                        method="delete"
                        class="flex"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="processing"
                        >
                            Hapus tautan WhatsApp
                        </Button>
                    </Form>
                </div>

                <div class="space-y-6">
                    <Form
                        :action="update.url('telegram')"
                        method="put"
                        class="space-y-4"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="telegram">ID Telegram</Label>
                            <Input
                                id="telegram"
                                name="identifier"
                                inputmode="numeric"
                                class="mt-1 block w-full"
                                :default-value="telegram ?? ''"
                                placeholder="123456789"
                            />
                            <p class="text-sm text-muted-foreground">
                                Angka ID akun Telegram, bukan nama pengguna.
                            </p>
                            <InputError :message="errors.identifier" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button type="submit" :disabled="processing">
                                {{
                                    processing
                                        ? 'Menyimpan…'
                                        : 'Simpan Telegram'
                                }}
                            </Button>
                            <p
                                v-if="recentlySuccessful"
                                class="text-sm text-muted-foreground"
                            >
                                Tersimpan.
                            </p>
                        </div>
                    </Form>

                    <Form
                        v-if="telegram"
                        :action="destroy.url('telegram')"
                        method="delete"
                        class="flex"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="processing"
                        >
                            Hapus tautan Telegram
                        </Button>
                    </Form>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
