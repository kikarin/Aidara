<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const props = defineProps<{ infoHeader: any }>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${props.infoHeader.program_latihan_id}/rencana-latihan` },
    { title: 'Tambah Rencana', href: '#' },
];
</script>

<template>
    <PageCreate
        title="Tambah Rencana Latihan"
        :breadcrumbs="breadcrumbs"
        :back-url="`/program-latihan/${props.infoHeader.program_latihan_id}/rencana-latihan`"
        :use-grid="true"
    >
        <Form mode="create" :info-header="infoHeader" />
    </PageCreate>
</template>
