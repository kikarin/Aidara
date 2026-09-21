<script setup lang="ts">
import type { ImgHTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        src: string;
        alt: string;
        lazy?: boolean;
        class?: ImgHTMLAttributes['class'];
    }>(),
    {
        lazy: true,
    },
);
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        :class="class"
        :loading="lazy ? 'lazy' : 'eager'"
        decoding="async"
        fetchpriority="auto"
    />
</template>
