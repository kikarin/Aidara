<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{
    tenagaPendukungId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Dokumen', href: `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen` },
    { title: 'Edit Dokumen', href: `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen/${props.item.id}/edit` },
]);
</script>

<template>
    <PageEdit title="Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/dokumen`">
        <FormDokumen :tenaga-pendukung-id="props.tenagaPendukungId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
