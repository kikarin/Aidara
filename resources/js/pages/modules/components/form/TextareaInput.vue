<script setup lang="ts">
defineProps<{
    modelValue: string;
    placeholder?: string;
    required?: boolean;
}>();
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <textarea
        class="border-input bg-background text-foreground placeholder:text-muted-foreground focus-visible:ring-ring min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        :placeholder="placeholder"
        :required="required"
        :value="modelValue"
        @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
</template>
