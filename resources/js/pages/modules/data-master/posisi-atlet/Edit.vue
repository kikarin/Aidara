<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Posisi Atlet', href: '/data-master/posisi-atlet' },
    { title: 'Edit Posisi Atlet', href: `/data-master/posisi-atlet/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Posisi Atlet" :breadcrumbs="breadcrumbs" back-url="/data-master/posisi-atlet">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
