<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const page = usePage();
const routeParams = computed(() => {
    if (page.props && page.props.ziggy && (page.props.ziggy as any).route_parameters) {
        return (page.props.ziggy as any).route_parameters;
    }
    // fallback: parse dari URL
    if (typeof window !== 'undefined') {
        const path = window.location.pathname.split('/');
        return {
            program_id: path[2],
            jenis_target: path[4],
        };
    }
    return {};
});
const programId = computed(() => routeParams.value.program_id);
const jenisTarget = computed(() => routeParams.value.jenis_target);

const props = defineProps<{
    infoHeader?: any;
}>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}` },
];

const columns = computed(() => {
    const baseColumns = [
        { key: 'deskripsi', label: 'Deskripsi Target' },
        { key: 'satuan', label: 'Satuan' },
        { key: 'nilai_target', label: 'Nilai Target' },
    ];

    // Kolom peruntukan hanya untuk target individu
    if (info.value.jenis_target === 'individu') {
        baseColumns.splice(1, 0, { key: 'peruntukan', label: 'Peruntukan' });
    }

    return baseColumns;
});

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${row.id}`),
        permission: 'Target Latihan Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${row.id}/edit`),
        permission: 'Target Latihan Edit',
    },
    {
        label: 'Statistik',
        onClick: () => router.visit(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${row.id}/statistik`),
        permission: 'Target Latihan Detail',
    },
    { label: 'Delete', onClick: () => pageIndex.value.handleDeleteRow(row), permission: 'Target Latihan Delete' },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post(
            `/program-latihan/${info.value.program_latihan_id}/target-latihan/${info.value.jenis_target}/destroy-selected`,
            { ids: selected.value },
        );
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message, variant: 'success' });
    } catch (error: any) {
        const message = error.response?.data?.message;
        toast({ title: message, variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    const deleteUrl = `/program-latihan/${info.value.program_latihan_id}/target-latihan/${info.value.jenis_target}/${row.id}`;
    console.log('Delete URL:', deleteUrl);
    console.log('program_latihan_id:', info.value.program_latihan_id, 'jenis_target:', info.value.jenis_target, 'row.id:', row.id);

    await router.delete(deleteUrl, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: (errors: any) => {
            console.error('Delete error:', errors);
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};

const info = computed(() => props.infoHeader || {});

// Perbaiki endpoint API agar mengirimkan peruntukan jika ada
const defaultApiEndpoint = computed(() => {
    let url = `/api/target-latihan?program_latihan_id=${info.value.program_latihan_id}&jenis_target=${info.value.jenis_target}`;
    if (info.value.peruntukan) {
        url += `&peruntukan=${info.value.peruntukan}`;
    }
    return url;
});
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Target Latihan"
            module-name="Target Latihan"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="`/program-latihan/${info.program_latihan_id}/target-latihan/${info.jenis_target}/create`"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            :api-endpoint="defaultApiEndpoint"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
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
                            <span class="text-sm font-medium">
                                {{ info.cabor_nama }}<template v-if="info.cabor_kategori_nama"> - {{ info.cabor_kategori_nama }}</template>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Periode:</span>
                            <span class="text-sm font-medium">{{
                                info.periode_mulai && info.periode_selesai ? `${info.periode_mulai} s/d ${info.periode_selesai}` : '-'
                            }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Jenis Target:</span>
                            <span class="text-sm font-medium">{{ info.jenis_target }}</span>
                        </div>
                        <div v-if="info.jenis_target === 'individu' && info.peruntukan" class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Peruntukan:</span>
                            <span class="text-sm font-medium">{{ info.peruntukan }}</span>
                        </div>
                    </div>
                </div>
            </template>
        </PageIndex>
    </div>
</template>
