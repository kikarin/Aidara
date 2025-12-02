<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import FormPrestasi from './Form.vue';

const props = defineProps<{ atletId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Prestasi', href: `/atlet/${props.atletId}/prestasi` },
    { title: 'Create Prestasi', href: `/atlet/${props.atletId}/prestasi/create` },
]);
</script>

<template>
    <PageCreate title="Create Prestasi" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/prestasi`">
        <FormPrestasi :atlet-id="props.atletId" mode="create" :initial-data="{}" />
    </PageCreate>
</template>
