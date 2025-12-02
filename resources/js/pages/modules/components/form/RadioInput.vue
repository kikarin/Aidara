<script setup lang="ts">
defineProps<{
    modelValue: string | number;
    required?: boolean;
    name: string;
    options: { value: string | number; label: string }[];
}>();
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex gap-4">
        <label v-for="option in options" :key="option.value" class="inline-flex cursor-pointer items-center space-x-2">
            <input
                type="radio"
                :name="name"
                :value="option.value"
                :checked="modelValue === option.value"
                :required="required"
                class="form-radio text-primary border-input focus:ring-ring"
                @change="emit('update:modelValue', option.value)"
            />
            <span class="text-sm">{{ option.label }}</span>
        </label>
    </div>
</template>
