<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';

withDefaults(
    defineProps<{
        fields?: number;
        columns?: number;
    }>(),
    {
        fields: 4,
        columns: 2,
    },
);
</script>

<template>
    <div class="grid gap-4" :class="columns === 2 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1'">
        <div v-for="field in fields" :key="field" class="space-y-2">
            <Skeleton class="h-4 w-32" />
            <Skeleton class="h-10 w-full rounded-md" />
        </div>
    </div>
</template>
