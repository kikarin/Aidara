<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{
    pelatihId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Dokumen', href: `/pelatih/${props.pelatihId}/dokumen` },
    { title: 'Edit Dokumen', href: `/pelatih/${props.pelatihId}/dokumen/${props.item.id}/edit` },
]);
</script>

<template>
    <PageEdit title="Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/pelatih/${props.pelatihId}/dokumen`">
        <FormDokumen :pelatih-id="props.pelatihId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
