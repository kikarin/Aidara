<script setup lang="ts">
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kecamatan', href: '/data-master/kecamatan' },
];

const columns = [{ key: 'nama', label: 'Nama Kecamatan', orderable: true }];

const pageIndex = ref();
const selected = ref<number[]>([]);

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/kecamatan/${row.id}`),
        permission: 'Mst Kecamatan Detail',
    },
];
</script>

<template>
    <PageIndex
        title="Kecamatan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        api-endpoint="/api/kecamatan"
        ref="pageIndex"
        :selected="selected"
        @update:selected="(val) => (selected = val)"
        :show-import="false"
        :show-create="false"
        :show-delete="false"
        :show-edit="false"
        :show-delete-selected="false"
    />
</template>
