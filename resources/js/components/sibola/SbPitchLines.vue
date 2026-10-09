<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'full' | 'half' }>(), { variant: 'full' });
</script>

<template>
    <svg
        class="pointer-events-none"
        viewBox="0 0 1200 640"
        preserveAspectRatio="xMidYMid slice"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
    >
        <template v-if="variant === 'full'">
            <rect x="40" y="40" width="1120" height="560" rx="4" />
            <path d="M600 40v560" />
            <circle cx="600" cy="320" r="96" />
            <circle cx="600" cy="320" r="5" fill="currentColor" stroke="none" />
            <path d="M40 170h180v300H40M40 245h66v150H40M220 262a96 96 0 0 1 0 116" />
            <path d="M1160 170H980v300h180M1160 245h-66v150h66M980 262a96 96 0 0 0 0 116" />
        </template>
        <template v-else>
            <path d="M0 40h1160v560H0" />
            <path d="M1160 170H980v300h180M1160 245h-66v150h66M980 262a96 96 0 0 0 0 116" />
            <path d="M0 224a96 96 0 0 1 0 192" />
        </template>
    </svg>
</template>
