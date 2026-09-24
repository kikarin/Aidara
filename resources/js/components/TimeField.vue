<script setup lang="ts">
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        showTimezone?: boolean;
    }>(),
    {
        modelValue: '',
        showTimezone: true,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const minutes = Array.from({ length: 12 }, (_, i) => String(i * 5).padStart(2, '0'));

const hourOptions = hours.map((h) => ({ value: h, label: h }));
const minuteOptions = minutes.map((m) => ({ value: m, label: m }));

const parts = computed(() => {
    const [h = '', m = ''] = String(props.modelValue ?? '').split(':');

    return {
        h: hours.includes(h) ? h : '06',
        m: minutes.includes(m) ? m : '00',
    };
});

const update = (h: string, m: string) => emit('update:modelValue', `${h}:${m}`);
</script>

<template>
    <div class="flex items-center gap-2">
        <SimpleSelect
            :model-value="parts.h"
            :options="hourOptions"
            trigger-class="border-border bg-background h-10 w-20 rounded-lg px-3 text-sm shadow-none"
            @update:model-value="(value) => update(String(value), parts.m)"
        />
        <span class="text-muted-foreground font-semibold">:</span>
        <SimpleSelect
            :model-value="parts.m"
            :options="minuteOptions"
            trigger-class="border-border bg-background h-10 w-20 rounded-lg px-3 text-sm shadow-none"
            @update:model-value="(value) => update(parts.h, String(value))"
        />
        <span v-if="showTimezone" class="text-muted-foreground text-xs">WIB</span>
    </div>
</template>
