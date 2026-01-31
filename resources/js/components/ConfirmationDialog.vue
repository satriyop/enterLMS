<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

defineProps<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
}>();

defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="(val) => !val && $emit('cancel')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ message }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button variant="outline" @click="$emit('cancel')">
                    {{ cancelLabel ?? 'Batal' }}
                </Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    @click="$emit('confirm')"
                >
                    {{ confirmLabel ?? 'Ya' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
