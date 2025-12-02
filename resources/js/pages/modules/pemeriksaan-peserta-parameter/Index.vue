<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    pemeriksaan: any;
    peserta: any;
    data: any[];
    total: number;
    currentPage: number;
    perPage: number;
    search: string;
}>();

const page = usePage();
const dataTable = computed<any[]>(() => (Array.isArray(page.props.data) ? page.props.data : []));
const pesertaFromProps = computed<string | null>(() => {
    const peserta = page.props.peserta as any;
    if (peserta && typeof peserta === 'object') {
        return peserta.peserta?.nama || peserta.nama || null;
    }
    return null;
});
const pesertaName = computed(() => {
    if (pesertaFromProps.value) return pesertaFromProps.value;
    if (dataTable.value.length > 0 && dataTable.value[0]?.peserta) return dataTable.value[0].peserta;
    return '-';
});

const jenisPeserta = computed(() => {
    return (
        page.props.jenis_peserta ||
        (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('jenis_peserta') || 'atlet' : 'atlet')
    );
});

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${props.pemeriksaan.id}/peserta?jenis_peserta=${jenisPeserta.value}` },
    {
        title: 'Parameter Peserta',
        href: `/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.peserta.id}/parameter?jenis_peserta=${jenisPeserta.value}`,
    },
];

const columns = [
    { key: 'parameter', label: 'Parameter' },
    { key: 'nilai', label: 'Nilai' },
    {
        key: 'trend',
        label: 'Trend',
        format: (row: any) => {
            if (row.trend === 'stabil') return '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">Stabil</span>';
            if (row.trend === 'penurunan')
                return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Penurunan</span>';
            if (row.trend === 'kenaikan')
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Kenaikan</span>';
            return row.trend;
        },
    },
];

const selected = ref<number[]>([]);

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () =>
            router.visit(`/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.peserta.id}/parameter/${row.id}?jenis_peserta=${jenisPeserta.value}`),
    },
    {
        label: 'Edit',
        onClick: () =>
            router.visit(
                `/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.peserta.id}/parameter/${row.id}/edit?jenis_peserta=${jenisPeserta.value}`,
            ),
    },
    { label: 'Delete', onClick: () => handleDeleteRow(row) },
];

const pageIndex = ref();

const handleDeleteRow = async (row: any) => {
    await router.delete(`/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.peserta.id}/parameter/${row.id}?jenis_peserta=${jenisPeserta.value}`, {
        onSuccess: () => {
            toast({ title: 'Data parameter peserta berhasil dihapus', variant: 'success' });
            if (pageIndex.value && pageIndex.value.fetchData) pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data parameter peserta', variant: 'destructive' });
        },
    });
};

const handleDeleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post(
            `/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.peserta.id}/parameter/destroy-selected?jenis_peserta=${jenisPeserta.value}`,
            { ids: selected.value },
        );
        selected.value = [];
        if (pageIndex.value && pageIndex.value.fetchData) pageIndex.value.fetchData();
        toast({ title: response.data?.message || 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus data', variant: 'destructive' });
    }
};
</script>

<template>
    <PageIndex
        title="Parameter Peserta"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="`/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter/create?jenis_peserta=${jenisPeserta}`"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="handleDeleteSelected"
        :api-endpoint="`/api/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter`"
        ref="pageIndex"
        :showImport="false"
        :on-delete-row="handleDeleteRow"
        :on-toast="toast"
    >
        <template #header-extra>
            <div class="bg-card mb-4 rounded-lg border p-4">
                <h3 class="mb-2 text-lg font-semibold">Informasi Pemeriksaan</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Nama Pemeriksaan:</span>
                        <span class="text-sm font-medium">{{ page.props.pemeriksaan?.nama_pemeriksaan }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                        <span class="text-sm font-medium">{{ page.props.pemeriksaan?.cabor?.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Kategori:</span>
                        <span class="text-sm font-medium">{{ page.props.pemeriksaan?.cabor_kategori?.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Tenaga Pendukung:</span>
                        <span class="text-sm font-medium">{{ page.props.pemeriksaan?.tenaga_pendukung?.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Peserta:</span>
                        <span class="text-sm font-medium">{{ pesertaName }}</span>
                    </div>
                </div>
            </div>
        </template>
    </PageIndex>
</template>
