<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: string | number;
    placeholder?: string;
    required?: boolean;
    options: { value: string | number; label: string }[];
}>();

const emit = defineEmits(['update:modelValue']);

// Filter out empty string values untuk SelectItem
const filteredOptions = computed(() => 
    props.options.filter(option => option.value !== '')
);

// Handle empty string as undefined untuk Select component
const selectValue = computed(() => 
    props.modelValue === '' ? undefined : String(props.modelValue)
);

const handleUpdate = (value: string | undefined) => {
    // Convert undefined back to empty string jika diperlukan
    emit('update:modelValue', value || '');
};
</script>

<template>
    <Select 
        :model-value="selectValue" 
        @update:modelValue="handleUpdate" 
        :required="required"
    >
        <SelectTrigger class="w-full min-w-[120px] relative z-10">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent class="z-50 max-h-[200px] overflow-auto">
            <SelectItem 
                v-for="option in filteredOptions" 
                :key="option.value" 
                :value="String(option.value)"
                class="cursor-pointer"
            >
                {{ option.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>