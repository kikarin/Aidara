<script setup lang="ts">
import ToastContainer from '@/components/ui/toast/ToastContainer.vue';
import { useLayoutBreadcrumbs } from '@/composables/useLayoutBreadcrumbs';
import type { BreadcrumbItemType } from '@/types';
import { watch } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { setBreadcrumbs } = useLayoutBreadcrumbs();

watch(
    () => props.breadcrumbs,
    (items) => setBreadcrumbs(items ?? []),
    { immediate: true, deep: true },
);
</script>

<template>
    <ToastContainer />
    <slot />
</template>
