<script setup lang="ts">
import DataTableShow from '@/pages/modules/components/DataTableShow.vue';
import { ref, watch } from 'vue';

interface Dokumen {
    id: number;
    jenis_dokumen?: { nama: string };
    nomor?: string;
    file_url?: string;
}

const props = defineProps<{
    dokumenList: Dokumen[];
    pelatihId?: number;
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
    { key: 'jenis_dokumen', label: 'Jenis Dokumen', format: (row: Dokumen) => row.jenis_dokumen?.nama || '-' },
    { key: 'nomor', label: 'Nomor' },
    {
        key: 'file_url',
        label: 'File',
        format: (row: Dokumen) =>
            row.file_url
                ? `<a href="${row.file_url}" target="_blank" class="text-blue-600 underline">Lihat File</a>`
                : '<span class="text-muted-foreground">-</span>',
    },
];
</script>

<template>
    <DataTableShow :columns="columns" :rows="props.dokumenList">
        <template #title>
            <h3 class="text-muted-foreground text-base font-semibold tracking-wide uppercase">Informasi Semua Dokumen</h3>
        </template>
    </DataTableShow>
</template>
