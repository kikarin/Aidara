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
    { title: 'Sertifikat', href: `/pelatih/${props.pelatihId}/sertifikat` },
    { title: 'Edit', href: `/pelatih/${props.pelatihId}/sertifikat/${props.item.id}/edit` },
]);

const title = computed(() => `Edit Sertifikat: ${props.item.nama_sertifikat || '-'}`);
</script>

<template>
    <PageEdit :title="title" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/sertifikat`">
        <Form :pelatih-id="props.pelatihId" :initial-data="props.item" mode="edit" />
    </PageEdit>
</template>
