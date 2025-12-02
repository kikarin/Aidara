<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{ pelatihId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Dokumen', href: `/pelatih/${props.pelatihId}/dokumen` },
    { title: 'Create Dokumen', href: `/pelatih/${props.pelatihId}/dokumen/create` },
]);
</script>

<template>
    <PageCreate title="Create Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/dokumen`">
        <FormDokumen :pelatih-id="props.pelatihId" mode="create" :initial-data="{}" />
    </PageCreate>
</template>
