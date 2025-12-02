<script setup lang="ts">
const props = defineProps<{ class?: string }>()
</script>
<template>
  <h5 :class="['mb-1 font-medium leading-none tracking-tight', props.class]">
    <slot />
  </h5>
</template> 