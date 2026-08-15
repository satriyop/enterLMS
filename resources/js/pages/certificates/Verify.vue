<script setup lang="ts">
// =============================================================================
// Public Certificate Verification Page
// Allows anyone to verify a certificate by entering its code
// =============================================================================

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, router } from '@inertiajs/vue3';
import { Shield, Search } from 'lucide-vue-next';
import { ref } from 'vue';

// =============================================================================
// State
// =============================================================================

const verificationCode = ref('');
const isSearching = ref(false);

// =============================================================================
// Actions
// =============================================================================

const handleVerify = () => {
    if (!verificationCode.value.trim()) return;

    isSearching.value = true;
    router.get(`/certificates/verify/${verificationCode.value.trim().toUpperCase()}`, {}, {
        onFinish: () => {
            isSearching.value = false;
        },
    });
};
</script>

<template>
    <Head title="Verifikasi Sertifikat" />

    <div class="flex min-h-screen items-center justify-center bg-surface-2 px-4">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gold-soft">
                    <Shield class="h-8 w-8 text-gold" />
                </div>
                <CardTitle class="text-editorial-h1">Verifikasi Sertifikat</CardTitle>
                <CardDescription>
                    Masukkan kode verifikasi untuk memastikan keaslian sertifikat
                </CardDescription>
            </CardHeader>

            <CardContent>
                <form @submit.prevent="handleVerify" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="code">Kode Verifikasi</Label>
                        <Input
                            id="code"
                            v-model="verificationCode"
                            placeholder="Contoh: A1B2C3D4E5F6G7H8"
                            class="font-mono uppercase"
                            :disabled="isSearching"
                        />
                        <p class="text-xs text-muted-foreground">
                            Kode verifikasi dapat ditemukan pada bagian bawah sertifikat
                        </p>
                    </div>

                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="!verificationCode.trim() || isSearching"
                    >
                        <Search class="mr-2 h-4 w-4" />
                        {{ isSearching ? 'Mencari...' : 'Verifikasi' }}
                    </Button>
                </form>

                <div class="mt-6 rounded-lg border border-border bg-surface-2 p-4">
                    <h4 class="mb-2 text-sm font-medium text-foreground">
                        Cara Verifikasi:
                    </h4>
                    <ol class="list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                        <li>Temukan kode verifikasi pada sertifikat</li>
                        <li>Masukkan kode pada kolom di atas</li>
                        <li>Klik tombol "Verifikasi"</li>
                        <li>Sistem akan menampilkan status sertifikat</li>
                    </ol>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
