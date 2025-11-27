<script setup lang="ts">
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { computed } from 'vue';

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        created_at?: string;
        updated_at?: string;
        created_by_user?: { name: string };
        updated_by_user?: { name: string };
    };
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kecamatan', href: '/data-master/kecamatan' },
    { title: 'Detail Kecamatan', href: `/data-master/kecamatan/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'ID', value: props.item.id },
    { label: 'Nama Kecamatan', value: props.item.nama },
]);

const actionFields = [
    { label: 'Created At', value: props.item.created_at || '-' },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: props.item.updated_at || '-' },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];
</script>

<template>
    <PageShow title="Kecamatan" :breadcrumbs="breadcrumbs" :fields="fields" :action-fields="actionFields" :back-url="'/data-master/kecamatan'" />
</template>
