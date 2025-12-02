<script setup lang="ts">
import { Input } from '@/components/ui/input';

defineProps<{
    modelValue: string | number;
    placeholder?: string;
    required?: boolean;
    type?: 'text' | 'email' | 'number';
}>();
</script>

<template>
    <Input
        :type="type ?? 'text'"
        :placeholder="placeholder"
        :required="required"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
</template>
