<script setup lang="ts">
import { inject, computed } from 'vue';
import { TABS_CONTEXT_KEY } from './tabs-context';

const props = defineProps<{
    value: string;
    forceMount?: boolean;
}>();

const context = inject(TABS_CONTEXT_KEY, null);

const isSelected = computed(() => context?.selectedValue.value === props.value);
</script>

<template>
    <div v-if="isSelected || forceMount" role="tabpanel" :data-state="isSelected ? 'active' : 'inactive'" class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
        <slot />
    </div>
</template> 