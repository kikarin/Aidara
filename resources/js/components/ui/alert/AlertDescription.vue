<script setup lang="ts">
const props = defineProps<{ class?: string }>()
</script>

<template>
  <div :class="['text-sm leading-relaxed opacity-90 [&_p]:leading-relaxed [&_ul]:mt-2 [&_li]:mb-1', props.class]">
    <slot />
  </div>
</template>