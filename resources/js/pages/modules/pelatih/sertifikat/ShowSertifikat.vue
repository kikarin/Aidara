<script setup lang="ts">
import DataTableShow from '@/pages/modules/components/DataTableShow.vue';
import { ref, watch } from 'vue';

interface Sertifikat {
    id: number;
    nama_sertifikat: string;
    penyelenggara?: string;
    tanggal_terbit?: string;
    file_url?: string;
}

const props = defineProps<{
    sertifikatList: Sertifikat[];
    pelatihId?: number;
    selectedIds?: number[];
    onEditSertifikat?: (sertifikat: Sertifikat) => void;
    onDeleteSertifikat?: (sertifikat: Sertifikat) => void;
    onDeleteSelectedSertifikat?: (ids: number[]) => void;
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
    { key: 'nama_sertifikat', label: 'Nama Sertifikat' },
    { key: 'penyelenggara', label: 'Penyelenggara' },
    {
        key: 'tanggal_terbit',
        label: 'Tanggal Terbit',
        format: (row: Sertifikat) =>
            row.tanggal_terbit
                ? new Date(row.tanggal_terbit).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                : '-',
    },
    {
        key: 'file_url',
        label: 'File',
        format: (row: Sertifikat) =>
            row.file_url
                ? `<a href="${row.file_url}" target="_blank" class="text-blue-600 underline">Lihat File</a>`
                : '<span class="text-muted-foreground">-</span>',
    },
];
</script>

<template>
    <DataTableShow :columns="columns" :rows="props.sertifikatList">
        <template #title>
            <h3 class="text-muted-foreground text-base font-semibold tracking-wide uppercase">Informasi Semua Sertifikat</h3>
        </template>
    </DataTableShow>
</template>
