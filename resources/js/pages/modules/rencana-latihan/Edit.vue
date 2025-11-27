<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ infoHeader: any; item: any }>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${props.infoHeader.program_latihan_id}/rencana-latihan` },
    { title: 'Edit Rencana', href: '#' },
];
</script>

<template>
    <PageEdit
        title="Rencana Latihan"
        :breadcrumbs="breadcrumbs"
        :back-url="`/program-latihan/${props.infoHeader.program_latihan_id}/rencana-latihan`"
        :use-grid="true"
    >
        <Form mode="edit" :info-header="infoHeader" :initial-data="item" />
    </PageEdit>
</template>
