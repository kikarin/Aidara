<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    backUrl?: string;
}>();

const goBack = () => {
    if (props.backUrl) {
        router.visit(props.backUrl);
    } else if (document.referrer) {
        router.visit(document.referrer);
    } else {
        router.visit('/');
    }
};
</script>

<template>
    <div class="grid grid-cols-12">
        <div class="col-span-6">
            <div class="bg-card flex items-center justify-between rounded-lg border px-6 py-4 shadow-sm">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ title }}
                </h1>
                <Button variant="secondary" @click="goBack">← Back</Button>
            </div>
        </div>
    </div>
</template>
