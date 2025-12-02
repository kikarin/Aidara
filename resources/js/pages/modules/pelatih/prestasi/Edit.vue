<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import Form from './Form.vue';

const props = defineProps<{
    pelatihId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Prestasi', href: `/pelatih/${props.pelatihId}/prestasi` },
    { title: 'Edit', href: `/pelatih/${props.pelatihId}/prestasi/${props.item.id}/edit` },
]);

const title = computed(() => `Prestasi: ${props.item.nama_event || '-'}`);
</script>

<template>
    <PageEdit :title="title" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/prestasi`">
        <Form :pelatih-id="props.pelatihId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
