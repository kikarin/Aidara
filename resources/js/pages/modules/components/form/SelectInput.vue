<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineProps<{
    modelValue: string | number;
    placeholder?: string;
    required?: boolean;
    options: { value: string | number; label: string }[];
}>();
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <Select :model-value="modelValue" @update:modelValue="emit('update:modelValue', $event)" :required="required">
        <SelectTrigger class="w-full">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
