<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import Form from './Form.vue';

const props = defineProps<{ pelatihId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Sertifikat', href: `/pelatih/${props.pelatihId}/sertifikat` },
    { title: 'Create', href: `/pelatih/${props.pelatihId}/sertifikat/create` },
]);
</script>

<template>
    <PageCreate title="Buat Sertifikat Pelatih" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/sertifikat`">
        <Form :pelatih-id="props.pelatihId" mode="create" />
    </PageCreate>
</template>
