<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

defineProps<{
    createUrl: string;
    selected: number[];
    onDeleteSelected: () => void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-start gap-2">
        <div>
            <Link :href="createUrl">
                <Button variant="default">+ Create</Button>
            </Link>
        </div>

        <div>
            <Button variant="destructive" size="sm" :disabled="selected.length === 0" @click="onDeleteSelected">
                Delete Selected ({{ selected.length }})
            </Button>
        </div>
    </div>
</template>
