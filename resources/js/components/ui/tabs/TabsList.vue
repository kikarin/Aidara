<script setup lang="ts">
import { inject } from 'vue';
import { TABS_CONTEXT_KEY } from './tabs-context';

const context = inject(TABS_CONTEXT_KEY, null);
</script>

<template>
    <div role="tablist" :aria-orientation="context?.orientation" class="flex items-center justify-center">
        <slot />
    </div>
</template> 