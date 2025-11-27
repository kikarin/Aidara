<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import Form from './Form.vue';

const props = defineProps<{ pelatihId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Prestasi', href: `/pelatih/${props.pelatihId}/prestasi` },
    { title: 'Create', href: `/pelatih/${props.pelatihId}/prestasi/create` },
]);
</script>

<template>
    <PageCreate title="Buat Prestasi Pelatih" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/prestasi`">
        <Form :pelatih-id="props.pelatihId" mode="create" />
    </PageCreate>
</template>
