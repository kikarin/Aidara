<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        lines?: number;
        showHeader?: boolean;
    }>(),
    {
        lines: 3,
        showHeader: true,
    },
);
</script>

<template>
    <div class="rounded-lg border border-border bg-card p-6 space-y-4">
        <div v-if="showHeader" class="space-y-2">
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-8 w-48" />
        </div>
        <div class="space-y-2">
            <Skeleton v-for="line in lines" :key="line" class="h-4" :class="line === lines ? 'w-2/3' : 'w-full'" />
        </div>
    </div>
</template>
