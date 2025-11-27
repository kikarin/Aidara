<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const props = defineProps<{
    infoHeader: any;
    data: any[];
    total: number;
    currentPage: number;
    perPage: number;
    search: string;
    sort: string;
    order: string;
}>();

const info = computed(() => props.infoHeader || {});
const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${info.value.program_latihan_id}/rencana-latihan` },
];

const columns = [
    { key: 'tanggal', label: 'Tanggal', format: (row: any) => (row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID') : '-') },
    { key: 'materi', label: 'Materi' },
    { key: 'lokasi_latihan', label: 'Lokasi Latihan' },
    { key: 'peserta', label: 'Peserta', orderable: false },
    {
        key: 'target_latihan',
        label: 'Target Latihan',
        format: (row: any) => row.target_latihan?.map((t: any) => t.deskripsi).join(', ') || '-',
        orderable: false,
    },
    { key: 'catatan', label: 'Catatan', orderable: false },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${row.id}`),
        permission: 'Rencana Latihan Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${row.id}/edit`),
        permission: 'Rencana Latihan Edit',
    },
    { label: 'Delete', onClick: () => pageIndex.value.handleDeleteRow(row), permission: 'Rencana Latihan Delete' },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/destroy-selected`, {
            ids: selected.value,
        });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message, variant: 'success' });
    } catch (error: any) {
        const message = error.response?.data?.message;
        toast({ title: message, variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    console.log('Delete URL:', `/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${row.id}`);
    console.log('program_latihan_id:', info.value.program_latihan_id, 'row.id:', row.id);
    await router.delete(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Rencana Latihan"
            module-name="Rencana Latihan"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="`/program-latihan/${info.program_latihan_id}/rencana-latihan/create`"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            :api-endpoint="`/api/rencana-latihan?program_latihan_id=${info.program_latihan_id}`"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
            :showKelola="true"
            :kelolaUrl="`/program-latihan/${info.program_latihan_id}/rencana-latihan/kelola-target-kelompok`"
            kelola-label="Pemetaan Nilai Kelompok"
        >
            <template #header-extra>
                <div class="bg-card mb-4 rounded-lg border p-4">
                    <h3 class="mb-2 text-lg font-semibold">Informasi Program Latihan</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Nama Program:</span>
                            <span class="text-sm font-medium">{{ info.nama_program }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                            <span class="text-sm font-medium"
                                >{{ info.cabor_nama }}<template v-if="info.cabor_kategori_nama"> - {{ info.cabor_kategori_nama }}</template></span
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Periode:</span>
                            <span class="text-sm font-medium">{{ info.periode_mulai }} s/d {{ info.periode_selesai }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <template #cell-peserta="{ row }">
                <BadgeGroup
                    :badges="[
                        {
                            label: 'Atlet',
                            value: row.jumlah_atlet || 0,
                            colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                            onClick: () => router.visit(`/program-latihan/${info.program_latihan_id}/rencana-latihan/${row.id}/index/atlet`),
                        },
                        {
                            label: 'Pelatih',
                            value: row.jumlah_pelatih || 0,
                            colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                            onClick: () => router.visit(`/program-latihan/${info.program_latihan_id}/rencana-latihan/${row.id}/index/pelatih`),
                        },
                        {
                            label: 'Tenaga Pendukung',
                            value: row.jumlah_tenaga_pendukung || 0,
                            colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            onClick: () =>
                                router.visit(`/program-latihan/${info.program_latihan_id}/rencana-latihan/${row.id}/index/tenaga-pendukung`),
                        },
                    ]"
                />
            </template>
        </PageIndex>
    </div>
</template>
