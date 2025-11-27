<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Pelatih', href: '/data-master/jenis-pelatih' },
    { title: 'Edit Jenis Pelatih', href: `/data-master/jenis-pelatih/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Jenis Pelatih" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-pelatih">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
