<script setup lang="ts">
import { Button } from '@/components/ui/button';
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
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ pending.options.title }}</DialogTitle>
                <DialogDescription v-if="pending.options.description">
                    {{ pending.options.description }}
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="settle(false)">
                    {{ pending.options.cancelText ?? 'Batal' }}
                </Button>
                <Button type="button" :variant="pending.options.variant === 'destructive' ? 'destructive' : 'default'" @click="settle(true)">
                    {{ pending.options.confirmText ?? 'Lanjutkan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
