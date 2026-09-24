<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string | number | null | undefined;
        placeholder?: string;
        required?: boolean;
        disabled?: boolean;
        options: Array<{ value: string | number; label: string; disabled?: boolean }>;
        triggerClass?: string;
        contentClass?: string;
    }>(),
    {
        placeholder: 'Pilih…',
        required: false,
        disabled: false,
        triggerClass: 'h-11 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 text-sm shadow-none',
        contentClass: 'z-50 max-h-64 overflow-auto rounded-xl',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const selectValue = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return undefined;
    }

    return String(props.modelValue);
});

const handleUpdate = (value: unknown) => {
    if (value === undefined || value === null || value === '') {
        emit('update:modelValue', '');

        return;
    }

    const asString = String(value);
    const match = props.options.find((option) => String(option.value) === asString);
    emit('update:modelValue', match ? match.value : asString);
};
</script>

<template>
    <Select :model-value="selectValue" :required="required" :disabled="disabled" @update:model-value="handleUpdate">
        <SelectTrigger :class="triggerClass">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent :class="contentClass">
            <SelectItem
                v-for="option in options"
                :key="String(option.value)"
                :value="String(option.value)"
                :disabled="option.disabled"
                class="cursor-pointer"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
