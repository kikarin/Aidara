<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    showSave?: boolean;
    showCancel?: boolean;
    saveText?: string;
    saveDisabled?: boolean;
}>();

const emit = defineEmits(['save', 'cancel']);
</script>

<template>
    <div class="mt-4 flex justify-end gap-2">
        <Button v-if="showSave !== false" type="button" variant="default" :disabled="saveDisabled" @click="emit('save')">
            {{ saveText || 'Save' }}
        </Button>
        <Button v-if="showCancel !== false" type="button" variant="outline" @click="emit('cancel')"> Cancel </Button>
    </div>
</template>
