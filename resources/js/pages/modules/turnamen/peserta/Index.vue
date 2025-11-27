<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import SelectTableMultiple from '@/pages/modules/components/SelectTableMultiple.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const page = usePage();
const turnamenId = page.props.turnamen_id as string;
const turnamen = page.props.turnamen as any;
const jenisPeserta = ref(page.props.jenis_peserta || 'atlet');

const jenisLabel: Record<string, string> = {
    atlet: 'Atlet',
    pelatih: 'Pelatih',
    'tenaga-pendukung': 'Tenaga Pendukung',
};

const breadcrumbs = [
    { title: 'Turnamen', href: '/turnamen' },
    { title: 'Peserta', href: '#' },
];

const columns = computed(() => {
    const baseColumns = [
        { key: 'nama', label: 'Nama' },
        {
            key: 'foto',
            label: 'Foto',
            format: (row: any) => {
                if (row.foto) {
                    return `<div class='cursor-pointer' onclick="window.open('${row.foto}', '_blank')">
          <img src='${row.foto}' alt='Foto ${row.nama}' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' />
        </div>`;
                }
                return '<div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">No</div>';
            },
        },
        {
            key: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
        },
        { key: 'tempat_lahir', label: 'Tempat Lahir' },
        {
            key: 'tanggal_lahir',
            label: 'Tanggal Lahir',
            format: (row: any) =>
                row.tanggal_lahir
                    ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                    : '-',
        },
        { key: 'no_hp', label: 'No HP' },
    ];

    if (jenisPeserta.value === 'atlet') {
        return [
            { key: 'nama', label: 'Nama' },
            { key: 'posisi_atlet_nama', label: 'Posisi', format: (row: any) => row.posisi_atlet_nama || '-' },
            ...baseColumns.slice(1),
        ];
    } else if (jenisPeserta.value === 'pelatih') {
        return [
            { key: 'nama', label: 'Nama' },
            { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih', format: (row: any) => row.jenis_pelatih_nama || '-' },
            ...baseColumns.slice(1),
        ];
    } else if (jenisPeserta.value === 'tenaga-pendukung') {
        return [
            { key: 'nama', label: 'Nama' },
            { key: 'jenis_tenaga_pendukung_nama', label: 'Jenis Tenaga Pendukung', format: (row: any) => row.jenis_tenaga_pendukung_nama || '-' },
            ...baseColumns.slice(1),
        ];
    }

    return baseColumns;
});

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

// State untuk add peserta
const showAddPeserta = ref(false);
const selectedPesertaToAdd = ref<number[]>([]);
const saving = ref(false);
const refreshKey = ref(0); // Key untuk memaksa SelectTableMultiple refresh

// Columns untuk SelectTableMultiple
const atletColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'posisi_atlet_nama', label: 'Posisi', format: (row: any) => row.posisi_atlet_nama || '-' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    { key: 'tempat_lahir', label: 'Tempat Lahir' },
    {
        key: 'tanggal_lahir',
        label: 'Tanggal Lahir',
        format: (row: any) =>
            row.tanggal_lahir
                ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                : '-',
    },
];

const pelatihColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih', format: (row: any) => row.jenis_pelatih_nama || '-' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    { key: 'tempat_lahir', label: 'Tempat Lahir' },
    {
        key: 'tanggal_lahir',
        label: 'Tanggal Lahir',
        format: (row: any) =>
            row.tanggal_lahir
                ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                : '-',
    },
];

const tenagaPendukungColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_tenaga_pendukung_nama', label: 'Jenis Tenaga Pendukung', format: (row: any) => row.jenis_tenaga_pendukung_nama || '-' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    { key: 'tempat_lahir', label: 'Tempat Lahir' },
    {
        key: 'tanggal_lahir',
        label: 'Tanggal Lahir',
        format: (row: any) =>
            row.tanggal_lahir
                ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                : '-',
    },
];

const getColumnsForJenisPeserta = () => {
    if (jenisPeserta.value === 'atlet') return atletColumns;
    if (jenisPeserta.value === 'pelatih') return pelatihColumns;
    if (jenisPeserta.value === 'tenaga-pendukung') return tenagaPendukungColumns;
    return [];
};

const getEndpointForAddPeserta = () => {
    const caborKategoriId = turnamen?.cabor_kategori_id;
    if (!caborKategoriId) return '';
    return `/web-api/turnamen/peserta-by-cabor-kategori?cabor_kategori_id=${caborKategoriId}&jenis_peserta=${jenisPeserta.value}&turnamen_id=${turnamenId}`;
};

const handleAddPeserta = async () => {
    if (!selectedPesertaToAdd.value.length) {
        return toast({ title: 'Pilih peserta yang akan ditambahkan', variant: 'destructive' });
    }

    saving.value = true;
    try {
        const response = await axios.post(`/web-api/turnamen/${turnamenId}/peserta`, {
            jenis_peserta: jenisPeserta.value,
            peserta_ids: selectedPesertaToAdd.value,
        });

        toast({ title: response.data.message || 'Peserta berhasil ditambahkan', variant: 'success' });
        selectedPesertaToAdd.value = [];
        // Refresh data table
        if (pageIndex.value?.fetchData) {
            pageIndex.value.fetchData();
        }
        // Refresh SelectTableMultiple untuk menghilangkan peserta yang sudah ditambahkan
        refreshKey.value++;
    } catch (error: any) {
        console.error('Error adding peserta:', error);
        toast({
            title: error.response?.data?.message || 'Gagal menambahkan peserta',
            variant: 'destructive',
        });
    } finally {
        saving.value = false;
    }
};

const actions = (row: any) => [
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        variant: 'destructive',
        permission: `Turnamen Delete`,
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        await axios.post(`/web-api/turnamen/${turnamenId}/peserta/${jenisPeserta.value}/destroy-selected`, {
            ids: selected.value,
        });
        selected.value = [];
        if (pageIndex.value.fetchData) pageIndex.value.fetchData();
        toast({ title: 'Data berhasil dihapus', variant: 'success' });
    } catch {
        toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    try {
        await axios.delete(`/web-api/turnamen/${turnamenId}/peserta/${jenisPeserta.value}/${row.id}`);
        toast({ title: 'Data berhasil dihapus', variant: 'success' });
        if (pageIndex.value.fetchData) pageIndex.value.fetchData();
    } catch {
        toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
    }
};

// Watch untuk mendeteksi perubahan jenisPeserta
watch(jenisPeserta, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Reset state untuk add peserta
        selectedPesertaToAdd.value = [];
        showAddPeserta.value = false;
        refreshKey.value++;
        
        // Refresh data table
        if (pageIndex.value?.fetchData) {
            setTimeout(() => {
                pageIndex.value.fetchData();
            }, 100);
        }
    }
});

// Watch untuk refresh SelectTableMultiple saat showAddPeserta dibuka
watch(showAddPeserta, (newValue) => {
    if (newValue) {
        // Reset selected dan refresh key untuk memastikan data terbaru
        selectedPesertaToAdd.value = [];
        refreshKey.value++;
    }
});
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            :title="`Peserta Turnamen (${jenisLabel[jenisPeserta] || jenisPeserta})`"
            module-name="Turnamen"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            :on-delete-row="deleteRow"
            :show-import="false"
            :create-url="''"
            :api-endpoint="`/web-api/turnamen/${turnamenId}/peserta?jenis_peserta=${jenisPeserta}`"
            ref="pageIndex"
            :limit="-1"
            :disable-length="true"
            :hide-search="false"
            :hide-pagination="true"
            :on-toast="toast"
        >
            <template #header-extra>
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Informasi Turnamen -->
                    <div class="bg-card rounded-lg border p-4">
                        <h3 class="mb-2 text-lg font-semibold">Informasi Turnamen</h3>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Nama Turnamen:</span>
                                <span class="text-sm font-medium">{{ turnamen?.nama }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Cabor Kategori:</span>
                                <span class="text-sm font-medium"
                                    >{{ turnamen?.cabor_kategori?.cabor?.nama }} - {{ turnamen?.cabor_kategori?.nama }}</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Periode:</span>
                                <span class="text-sm font-medium">
                                    {{
                                        turnamen?.tanggal_mulai
                                            ? new Date(turnamen.tanggal_mulai).toLocaleDateString('id-ID', {
                                                  day: 'numeric',
                                                  month: 'long',
                                                  year: 'numeric',
                                              })
                                            : '-'
                                    }}
                                    s/d
                                    {{
                                        turnamen?.tanggal_selesai
                                            ? new Date(turnamen.tanggal_selesai).toLocaleDateString('id-ID', {
                                                  day: 'numeric',
                                                  month: 'long',
                                                  year: 'numeric',
                                              })
                                            : '-'
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Lokasi:</span>
                                <span class="text-sm font-medium">{{ turnamen?.lokasi }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Jenis Peserta -->
                    <div class="bg-card rounded-lg border p-4">
                        <h3 class="mb-2 text-lg font-semibold">Filter Peserta</h3>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Jenis Peserta:</label>
                            <Select v-model="jenisPeserta">
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="jenisLabel[jenisPeserta] || 'Pilih jenis peserta'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="atlet">Atlet</SelectItem>
                                    <SelectItem value="pelatih">Pelatih</SelectItem>
                                    <SelectItem value="tenaga-pendukung">Tenaga Pendukung</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Add Peserta Section -->
                <div v-if="turnamen?.cabor_kategori_id" class="mb-4 mt-4">
                    <div class="bg-card rounded-lg border p-4">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Tambah Peserta {{ jenisLabel[jenisPeserta] || jenisPeserta }}</h3>
                            <Button v-if="!showAddPeserta" @click="showAddPeserta = true" variant="default">
                                Tambah Peserta
                            </Button>
                            <Button v-else @click="showAddPeserta = false" variant="outline">Batal</Button>
                        </div>

                        <div v-if="showAddPeserta" class="space-y-4">
                            <SelectTableMultiple
                                :key="`${jenisPeserta}-${turnamenId}-${refreshKey}`"
                                :label="`Pilih ${jenisLabel[jenisPeserta] || jenisPeserta}`"
                                :endpoint="getEndpointForAddPeserta()"
                                :columns="getColumnsForJenisPeserta()"
                                id-key="id"
                                name-key="nama"
                                :selected-ids="selectedPesertaToAdd"
                                @update:selected-ids="(ids: number[]) => (selectedPesertaToAdd = ids)"
                            />

                            <div class="flex justify-end gap-2">
                                <Button @click="showAddPeserta = false" variant="outline">Batal</Button>
                                <Button @click="handleAddPeserta" :disabled="saving || !selectedPesertaToAdd.length">
                                    {{ saving ? 'Menyimpan...' : 'Simpan' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </PageIndex>
    </div>
</template>
