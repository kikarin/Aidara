<script setup lang="ts">
import DataTableShow from '@/pages/modules/components/DataTableShow.vue';
import { ref, watch } from 'vue';

interface Prestasi {
    id: number;
    nama_event: string;
    tingkat?: { nama: string };
    tanggal?: string;
    peringkat?: string;
    keterangan?: string;
    file_url?: string;
}

const props = defineProps<{
    prestasiList: Prestasi[];
    tenagaPendukungId?: number;
    selectedIds?: number[];
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
    { key: 'nama_event', label: 'Nama Event' },
    { key: 'tingkat', label: 'Tingkat', format: (row: Prestasi) => row.tingkat?.nama || '-' },
    {
        key: 'tanggal',
        label: 'Tanggal',
        format: (row: Prestasi) =>
            row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' }) : '-',
    },
    { key: 'peringkat', label: 'Peringkat' },
    { key: 'keterangan', label: 'Keterangan' },
];
</script>

<template>
    <DataTableShow :columns="columns" :rows="props.prestasiList">
        <template #title>
            <h3 class="text-muted-foreground text-base font-semibold tracking-wide uppercase">Informasi Semua Prestasi</h3>
        </template>
    </DataTableShow>
</template>
