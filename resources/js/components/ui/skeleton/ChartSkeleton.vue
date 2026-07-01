<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        height?: string;
    }>(),
    {
        height: 'h-[350px]',
    },
);
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4">
        <div class="flex items-center justify-between">
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-8 w-24 rounded-md" />
        </div>
        <Skeleton :class="['w-full rounded-lg', height]" />
        <div class="flex justify-center gap-4">
            <Skeleton class="h-3 w-16 rounded-full" />
            <Skeleton class="h-3 w-16 rounded-full" />
            <Skeleton class="h-3 w-16 rounded-full" />
        </div>
    </div>
</template>
