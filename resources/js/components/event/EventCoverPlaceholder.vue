<template>
    <div class="flex size-full items-center justify-center overflow-hidden bg-(--wp-accent-soft)" aria-hidden="true">
        <svg viewBox="0 0 320 200" class="h-[78%] w-auto text-(--wp-accent)" fill="none" stroke="currentColor">
            <rect
                v-for="lane in 4"
                :key="lane"
                :x="36 + (lane - 1) * 14"
                :y="28 + (lane - 1) * 14"
                :width="248 - (lane - 1) * 28"
                :height="144 - (lane - 1) * 28"
                :rx="72 - (lane - 1) * 14"
                stroke-width="1.5"
                :opacity="1 - (lane - 1) * 0.18"
            />
            <line x1="160" y1="28" x2="160" y2="70" stroke-width="2.5" stroke-dasharray="4 5" />
            <circle cx="160" cy="100" r="16" stroke-width="1.5" opacity="0.5" />
            <path d="M153 100h14M160 93v14" stroke-width="1.5" opacity="0.5" stroke-linecap="round" />
        </svg>
    </div>
</template>
