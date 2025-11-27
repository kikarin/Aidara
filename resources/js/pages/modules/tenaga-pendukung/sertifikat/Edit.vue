<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import Form from './Form.vue';

const props = defineProps<{
    tenagaPendukungId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Sertifikat', href: `/tenaga-pendukung/${props.tenagaPendukungId}/sertifikat` },
    { title: 'Edit', href: `/tenaga-pendukung/${props.tenagaPendukungId}/sertifikat/${props.item.id}/edit` },
]);

const title = computed(() => `Edit Sertifikat: ${props.item.nama_sertifikat || '-'}`);
</script>

<template>
    <PageEdit :title="title" :breadcrumbs="breadcrumbs" :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/sertifikat`">
        <Form :tenaga-pendukung-id="props.tenagaPendukungId" :initial-data="props.item" mode="edit" />
    </PageEdit>
</template>
