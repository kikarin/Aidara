<script setup lang="ts">
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { computed } from 'vue';

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        id_kecamatan?: number;
        created_at?: string;
        updated_at?: string;
        created_by_user?: { name: string };
        updated_by_user?: { name: string };
    };
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Desa/Kelurahan', href: '/data-master/desa' },
    { title: 'Detail Desa/Kelurahan', href: `/data-master/desa/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'ID', value: props.item.id },
    { label: 'Nama Desa/Kelurahan', value: props.item.nama },
    { label: 'ID Kecamatan', value: props.item.id_kecamatan || '-' },
]);

const actionFields = [
    { label: 'Dibuat Pada', value: props.item.created_at || '-' },
    { label: 'Dibuat Oleh', value: props.item.created_by_user?.name || '-' },
    { label: 'Diperbarui Pada', value: props.item.updated_at || '-' },
    { label: 'Diperbarui Oleh', value: props.item.updated_by_user?.name || '-' },
];
</script>

<template>
    <PageShow title="Desa/Kelurahan" :breadcrumbs="breadcrumbs" :fields="fields" :action-fields="actionFields" :back-url="'/data-master/desa'" />
</template>
