<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';

const { pending, settle } = useConfirm();
</script>

<template>
    <Dialog
        :open="pending.open"
        @update:open="
            (value) => {
                if (!value) settle(false);
            }
        "
    >
        <DialogContent class="bg-card rounded-3xl border-(--wp-hairline) p-6 sm:max-w-md">
            <DialogHeader class="gap-1.5">
                <DialogTitle class="text-foreground text-lg font-semibold tracking-tight">{{ pending.options.title }}</DialogTitle>
                <DialogDescription v-if="pending.options.description" class="text-muted-foreground text-sm">
                    {{ pending.options.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="mt-2 gap-2">
                <button type="button" class="wp-btn wp-btn-quiet justify-center px-4 py-2 text-sm" @click="settle(false)">
                    {{ pending.options.cancelText ?? 'Batal' }}
                </button>
                <button
                    type="button"
                    class="wp-btn justify-center px-5 py-2.5 text-sm"
                    :class="
                        pending.options.variant === 'destructive'
                            ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90 focus-visible:outline-destructive'
                            : 'wp-btn-primary'
                    "
                    @click="settle(true)"
                >
                    {{ pending.options.confirmText ?? 'Lanjutkan' }}
                </button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
