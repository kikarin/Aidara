<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const { toast } = useToast();
const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'All Parameter', href: '/pemeriksaan-parameter/AllParameter' },
];

const columns = [
    { key: 'nama', label: 'Nama Parameter', orderable: false },
    { key: 'satuan', label: 'Satuan', orderable: false },
    { key: 'jumlah_pemeriksaan', label: 'Jumlah Pemeriksaan', orderable: false },
];

const selected = ref<number[]>([]);
const pageIndex = ref();

const actions = (row: any) => [
    {
        label: 'Statistik',
        onClick: () => router.visit(`/pemeriksaan-parameter/AllParameter/${row.id}/statistik`),
    },
];
</script>

<template>
    <PageIndex
        title="All Parameter"
        module-name="All Parameter"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="'/api/pemeriksaan-parameter/AllParameter'"
        ref="pageIndex"
        :on-toast="toast"
        :on-delete-row="deleteRow"
        :showImport="false"
        :showCreate="false"
        :showDelete="false"
        :showEdit="false"
        :disable-length="true"
        :hide-pagination="true"
        :hide-search="true"
        :hide-select-all="true"
        :hide-select="true"
    >
    </PageIndex>
</template>
