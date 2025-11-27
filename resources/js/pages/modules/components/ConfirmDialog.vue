<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    title?: string;
    description?: string;
}>();

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const internalShow = ref(false);

watch(
    () => props.show,
    (val) => (internalShow.value = val),
);
</script>

<template>
    <Dialog v-model:open="internalShow">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>{{ props.title || 'Konfirmasi' }}</DialogTitle>
                <p class="text-muted-foreground text-sm">
                    {{ props.description || 'Apakah kamu yakin ingin melanjutkan?' }}
                </p>
            </DialogHeader>
            <DialogFooter class="mt-4">
                <Button variant="outline" @click="emit('cancel')">Batal</Button>
                <Button variant="destructive" @click="emit('confirm')">Ya, Hapus</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
