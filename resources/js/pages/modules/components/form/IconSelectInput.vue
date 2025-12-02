<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import * as LucideIcons from 'lucide-vue-next';

defineProps<{
    modelValue: string;
    placeholder?: string;
    required?: boolean;
}>();
const emit = defineEmits(['update:modelValue']);

const iconOptions = Object.keys(LucideIcons)
    .filter((k) => k !== 'default')
    .map((k) => ({
        value: k,
        label: k,
        icon: k,
    }));
</script>

<template>
    <Select :model-value="modelValue" @update:modelValue="emit('update:modelValue', $event)" :required="required">
        <SelectTrigger class="w-full">
            <SelectValue :placeholder="placeholder">
                <template v-if="modelValue">
                    <component :is="LucideIcons[modelValue as keyof typeof LucideIcons]" class="mr-2 inline-block h-4 w-4" />
                    {{ modelValue }}
                </template>
            </SelectValue>
        </SelectTrigger>
        <SelectContent class="max-h-[300px]">
            <div class="grid grid-cols-4 gap-2 p-2">
                <SelectItem
                    v-for="option in iconOptions"
                    :key="option.value"
                    :value="option.value"
                    class="hover:bg-accent flex cursor-pointer items-center gap-2 rounded-md p-2"
                >
                    <component :is="LucideIcons[option.icon as keyof typeof LucideIcons]" class="h-4 w-4" />
                    <span class="text-sm">{{ option.label }}</span>
                </SelectItem>
            </div>
        </SelectContent>
    </Select>
</template>
