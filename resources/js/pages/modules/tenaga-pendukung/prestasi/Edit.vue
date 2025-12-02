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
    { title: 'Prestasi', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi` },
    { title: 'Edit', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi/${props.item.id}/edit` },
]);

const title = computed(() => `Edit Prestasi: ${props.item.nama_event || '-'}`);
</script>

<template>
    <PageEdit :title="title" :breadcrumbs="breadcrumbs" :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`">
        <Form :tenaga-pendukung-id="props.tenagaPendukungId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
