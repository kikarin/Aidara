<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        count?: number;
    }>(),
    {
        count: 4,
    },
);
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="n in count" :key="n" class="overflow-hidden rounded-xl border bg-card p-6">
            <div class="flex items-center justify-between">
                <div class="space-y-3">
                    <Skeleton class="h-4 w-24" />
                    <Skeleton class="h-8 w-16" />
                </div>
                <Skeleton class="h-16 w-16 rounded-full" />
            </div>
        </div>
    </div>
</template>
