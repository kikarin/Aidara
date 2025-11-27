<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';
import PesertaModal from './components/PesertaModal.vue';

const breadcrumbs = [{ title: 'Cabor', href: '/cabor' }];

const columns = [
    { key: 'nama', label: 'Nama Cabor' },
    { key: 'peserta', label: 'Peserta', sortable: false, orderable: false },
    { key: 'deskripsi', label: 'Deskripsi' },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const showPesertaModal = ref(false);
const selectedPesertaData = ref<any[]>([]);
const selectedPesertaTipe = ref<string>('');
const selectedCaborId = ref<number | null>(null);

const actions = (row: any) => [
    { label: 'Detail', onClick: () => router.visit(`/cabor/${row.id}`), permission: 'Cabor Detail' },
    { label: 'Edit', onClick: () => router.visit(`/cabor/${row.id}/edit`), permission: 'Cabor Edit' },
    { label: 'Delete', onClick: () => pageIndex.value.handleDeleteRow(row), permission: 'Cabor Delete' },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post('/cabor/destroy-selected', { ids: selected.value });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message, variant: 'success' });
    } catch (error: any) {
        const message = error.response?.data?.message;
        toast({ title: message, variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    await router.delete(`/cabor/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};

const handlePesertaClick = async (caborId: number, tipe: string) => {
    try {
        const response = await axios.get(`/cabor/${caborId}/peserta/${tipe}`);
        selectedPesertaData.value = response.data.data;
        selectedPesertaTipe.value = tipe;
        selectedCaborId.value = caborId;
        showPesertaModal.value = true;
    } catch (error) {
        console.error('Gagal mengambil data peserta:', error);
        toast({ title: 'Gagal mengambil data peserta', variant: 'destructive' });
    }
};

const closePesertaModal = () => {
    showPesertaModal.value = false;
    selectedPesertaData.value = [];
    selectedPesertaTipe.value = '';
    selectedCaborId.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Cabor"
            module-name="Cabor"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/cabor/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/cabor"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row-confirm="deleteRow"
            :show-import="false"
        >
            <template #cell-peserta="{ row }">
                <BadgeGroup
                    :badges="[
                        {
                            label: 'Atlet',
                            value: row.jumlah_atlet || 0,
                            colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                            onClick: () => handlePesertaClick(row.id, 'atlet'),
                        },
                        {
                            label: 'Pelatih',
                            value: row.jumlah_pelatih || 0,
                            colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                            onClick: () => handlePesertaClick(row.id, 'pelatih'),
                        },
                        {
                            label: 'Tenaga Pendukung',
                            value: row.jumlah_tenaga_pendukung || 0,
                            colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            onClick: () => handlePesertaClick(row.id, 'tenaga_pendukung'),
                        },
                    ]"
                />
            </template>
        </PageIndex>

        <!-- Modal Peserta -->
        <PesertaModal
            :show="showPesertaModal"
            :data="selectedPesertaData"
            :tipe="selectedPesertaTipe"
            :cabor-id="selectedCaborId"
            @close="closePesertaModal"
        />
    </div>
</template>
