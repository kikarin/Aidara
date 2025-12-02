<script setup lang="ts">
import DataTableShow from '@/pages/modules/components/DataTableShow.vue';
import { ref, watch } from 'vue';

interface CaborData {
    id: number;
    cabor_id: number;
    cabor_kategori_id: number;
    tenaga_pendukung_id: number;
    jenis_tenaga_pendukung_id: number;
    is_active: boolean;
    created_at: string;
    cabor: {
        id: number;
        nama: string;
        deskripsi: string;
    };
    cabor_kategori: {
        id: number;
        nama: string;
        jenis_kelamin: string;
        deskripsi: string;
    };
    jenis_tenaga_pendukung: {
        id: number;
        nama: string;
    };
}

const props = defineProps<{
    caborList: CaborData[];
    tenagaPendukungId?: number;
    selectedIds?: number[];
    onEditCabor?: (cabor: CaborData) => void;
    onDeleteCabor?: (cabor: CaborData) => void;
    onDeleteSelectedCabor?: (ids: number[]) => void;
}>();

const selected = ref<number[]>(props.selectedIds ? [...props.selectedIds] : []);

watch(
    () => props.selectedIds,
    (val) => {
        if (val && JSON.stringify(val) !== JSON.stringify(selected.value)) {
            selected.value = [...val];
        }
    },
);

const columns = [
    {
        key: 'cabor.nama',
        label: 'Cabor',
        format: (row: CaborData) => row.cabor?.nama || '-',
    },
    {
        key: 'cabor_kategori.nama',
        label: 'Kategori',
        format: (row: CaborData) => row.cabor_kategori?.nama || '-',
    },
    {
        key: 'jenis_tenaga_pendukung.nama',
        label: 'Jenis Tenaga Pendukung',
        format: (row: CaborData) => row.jenis_tenaga_pendukung?.nama || '-',
    },
    {
        key: 'is_active',
        label: 'Status',
        format: (row: CaborData) =>
            row.is_active
                ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Aktif</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Nonaktif</span>',
    },
];
</script>

<template>
    <DataTableShow :columns="columns" :rows="props.caborList">
        <template #title>
            <h3 class="text-muted-foreground text-base font-semibold tracking-wide uppercase">Informasi Semua Cabor</h3>
        </template>
    </DataTableShow>
</template>
