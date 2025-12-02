<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

interface Pemeriksaan {
    id: number;
    nama_pemeriksaan: string;
    cabor: { nama: string };
    cabor_kategori: { nama: string };
    tenaga_pendukung: { nama: string };
}

const { toast } = useToast();
const page = usePage();
const pemeriksaan = computed(() => (page.props.pemeriksaan as Pemeriksaan) || ({} as Pemeriksaan));
const pemeriksaanId = computed(() => pemeriksaan.value?.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Parameter Pemeriksaan', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter` },
];

const columns = [
    { key: 'nama_parameter', label: 'Nama Parameter' },
    { key: 'satuan', label: 'Satuan' },
];

const selected = ref<number[]>([]);
const pageIndex = ref();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${row.id}`),
        permission: 'Pemeriksaan Parameter Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${row.id}/edit`),
        permission: 'Pemeriksaan Parameter Edit',
    },
    { label: 'Delete', onClick: () => pageIndex.value.handleDeleteRow(row), permission: 'Pemeriksaan Parameter Delete' },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/destroy-selected`, { ids: selected.value });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message || 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus data', variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    await router.delete(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${row.id}`, {
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
    <PageIndex
        title="Parameter Pemeriksaan"
        module-name="Pemeriksaan Parameter"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="`/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter/create`"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="`/api/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter`"
        ref="pageIndex"
        :on-toast="toast"
        :on-delete-row="deleteRow"
        :showImport="false"
    >
        <template #header-extra>
            <div class="bg-card mb-4 rounded-lg border p-4">
                <h3 class="mb-2 text-lg font-semibold">Informasi Pemeriksaan</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Nama Pemeriksaan:</span>
                        <span class="text-sm font-medium">{{ pemeriksaan.nama_pemeriksaan }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                        <span class="text-sm font-medium">{{ pemeriksaan.cabor?.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Kategori:</span>
                        <span class="text-sm font-medium">{{ pemeriksaan.cabor_kategori?.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Tenaga Pendukung:</span>
                        <span class="text-sm font-medium">{{ pemeriksaan.tenaga_pendukung?.nama }}</span>
                    </div>
                </div>
            </div>
        </template>
    </PageIndex>
</template>
