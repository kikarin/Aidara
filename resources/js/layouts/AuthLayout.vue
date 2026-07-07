<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
    description?: string;
    wide?: boolean;
    indexable?: boolean;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description" :wide="wide" :indexable="indexable">
        <slot />
    </AuthLayout>
</template>
