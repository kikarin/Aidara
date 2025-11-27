<script setup lang="ts">
import { inject, computed } from 'vue';
import { TABS_CONTEXT_KEY } from './tabs-context';

const props = defineProps<{
    value: string;
    disabled?: boolean;
}>();

const context = inject(TABS_CONTEXT_KEY, null);

const isSelected = computed(() => context?.selectedValue.value === props.value);

const handleClick = () => {
    if (props.disabled) return;
    context?.onValueChange(props.value);
};
</script>

<template>
    <button  
        type="button"
        role="tab"
        :aria-selected="isSelected"
        :data-state="isSelected ? 'active' : 'inactive'"
        :disabled="disabled"
        @click="handleClick"
        class="inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
    >
        <slot />
    </button>
</template>
