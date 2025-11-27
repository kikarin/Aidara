<script setup lang="ts">
defineProps<{
    badges: Array<{
        label: string;
        value: number | string;
        colorClass: string;
        onClick?: () => void;
    }>;
}>();
</script>

<template>
    <div class="flex flex-col items-start gap-1">
        <button
            v-for="(badge, i) in badges"
            :key="i"
            type="button"
            :class="`inline-flex w-fit cursor-pointer items-center rounded-full px-2 py-0.5 text-[11px] font-medium ${badge.colorClass}`"
            @click="badge.onClick"
        >
            <span class="mr-1 font-semibold">{{ badge.value }}</span> {{ badge.label }}
        </button>
    </div>
</template>
