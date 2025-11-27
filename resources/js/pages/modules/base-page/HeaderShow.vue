<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <!-- Title -->
        <h1 class="text-2xl font-semibold tracking-tight">
            {{ title }}
        </h1>

        <!-- Slot untuk tombol -->
        <div class="flex flex-wrap items-center gap-2">
            <slot />
        </div>
    </div>
</template>
