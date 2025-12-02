<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

interface Pemeriksaan {
    id: number;
    nama_pemeriksaan: string;
    cabor: { nama: string };
    cabor_kategori: { nama: string };
    tenaga_pendukung: { nama: string };
}

const page = usePage();
const jenisPeserta = computed(() => (page.props.jenis_peserta as string) || 'atlet');
const pemeriksaanData = computed(() => (page.props.pemeriksaan as Pemeriksaan) || ({} as Pemeriksaan));

const props = defineProps<{
    pemeriksaan: any;
    items?: any;
}>();

onMounted(() => {
    console.log('Page props:', page.props);
    console.log('Items from props:', props.items);
    if (props.items?.data && props.items.data.length > 0) {
        console.log('First item:', props.items.data[0]);
        console.log('Peserta in first item:', props.items.data[0].peserta);
    }
});

const { toast } = useToast();
const pageIndex = ref();

const handleDeleteRow = async (row: any) => {
    console.log('row for delete:', row);
    try {
        await axios.delete(`/pemeriksaan/${row.pemeriksaan_id}/peserta/${row.id}`);
        if (pageIndex.value && pageIndex.value.fetchData) {
            pageIndex.value.fetchData();
        }
        toast({ title: 'Peserta berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus peserta', variant: 'destructive' });
    }
};

const deleteSelected = async () => {
    try {
        await axios.post(`/pemeriksaan/${props.pemeriksaan.id}/peserta/destroy-selected`, {
            ids: selected.value,
        });
        selected.value = [];
        if (pageIndex.value && pageIndex.value.fetchData) {
            pageIndex.value.fetchData();
        }
        toast({ title: 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Terjadi kesalahan', variant: 'destructive' });
    }
};

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${pemeriksaanData.value.id}/peserta?jenis_peserta=${jenisPeserta.value}` },
];

const pesertaCache = ref<Record<string, any>>({});

const fetchPesertaDetail = async (jenis: string, id: number) => {
    const cacheKey = `${jenis}_${id}`;
    if (pesertaCache.value[cacheKey]) return pesertaCache.value[cacheKey];
    try {
        const { data } = await axios.get(`/api/${jenis}/${id}`);
        pesertaCache.value[cacheKey] = data.data || data;
        return pesertaCache.value[cacheKey];
    } catch {
        pesertaCache.value[cacheKey] = null;
        return null;
    }
};

const getPesertaData = (row: any) => {
    if (typeof row.peserta === 'object' && row.peserta !== null) return row.peserta;
    let jenis = '';
    if (row.peserta_type?.includes('Atlet')) jenis = 'atlet';
    else if (row.peserta_type?.includes('Pelatih')) jenis = 'pelatih';
    else if (row.peserta_type?.includes('TenagaPendukung')) jenis = 'tenaga-pendukung';
    const cacheKey = `${jenis}_${row.peserta_id}`;
    if (!pesertaCache.value[cacheKey]) {
        fetchPesertaDetail(jenis, row.peserta_id);
        return {};
    }
    return pesertaCache.value[cacheKey];
};

watch(pesertaCache, () => {}, { deep: true });

const fotoColumn = {
    key: 'peserta.foto',
    label: 'Foto',
    format: (row: any) => {
        const foto = getPesertaData(row)?.foto;
        const nama = getPesertaData(row)?.nama || 'Peserta';

        if (foto) {
            return `
                <div class='cursor-pointer' onclick="window.open('${foto}', '_blank')">
                    <img src='${foto}' alt='Foto ${nama}' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' />
                </div>
            `;
        }

        return `<div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">No</div>`;
    },
};

const baseColumns = [
    {
        key: 'status',
        label: 'Status',
        format: (row: any) => row.status?.nama || '-',
    },
    {
        key: 'catatan_umum',
        label: 'Catatan',
        format: (row: any) => row.catatan_umum || '-',
    },
    {
        key: 'parameter_peserta',
        label: 'Parameter',
    },
];

const specificLabel: Record<string, string> = {
    atlet: 'Nama Atlet',
    pelatih: 'Nama Pelatih',
    'tenaga-pendukung': 'Nama Tenaga Pendukung',
};

const columns = computed(() => {
    const labelNama = specificLabel[jenisPeserta.value] || 'Nama Peserta';

    return [
        {
            key: 'peserta.nama',
            label: labelNama,
            format: (row: any) => getPesertaData(row)?.nama || '-',
        },
        fotoColumn,
        ...baseColumns,
        {
            key: 'peserta.jenis_kelamin',
            label: 'Jenis Kelamin',
            format: (row: any) => {
                const jenisKelamin = getPesertaData(row)?.jenis_kelamin;
                return jenisKelamin === 'L' ? 'Laki-laki' : jenisKelamin === 'P' ? 'Perempuan' : '-';
            },
        },
        {
            key: 'peserta.tempat_lahir',
            label: 'Tempat Lahir',
            format: (row: any) => getPesertaData(row)?.tempat_lahir || '-',
        },
        {
            key: 'peserta.tanggal_lahir',
            label: 'Tanggal Lahir',
            format: (row: any) => {
                const tanggalLahir = getPesertaData(row)?.tanggal_lahir;
                return tanggalLahir
                    ? new Date(tanggalLahir).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'numeric',
                          year: 'numeric',
                      })
                    : '-';
            },
        },
    ];
});
const selected = ref<number[]>([]);

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pemeriksaan/${props.pemeriksaan.id}/peserta/${row.id}`),
        permission: 'Pemeriksaan Peserta Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pemeriksaan/${props.pemeriksaan.id}/peserta/${row.id}/edit?jenis_peserta=${jenisPeserta.value}`),
        permission: 'Pemeriksaan Peserta Edit',
    },
    {
        label: 'Delete',
        onClick: () => handleDeleteRow(row),
        permission: 'Pemeriksaan Peserta Delete',
    },
];

const getPesertaLabel = computed(() => {
    switch (jenisPeserta.value) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga_pendukung':
            return 'Tenaga Pendukung';
        default:
            return 'Atlet';
    }
});
</script>

<template>
    <PageIndex
        :title="`Peserta ${getPesertaLabel}`"
        module-name="Pemeriksaan Peserta"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="`/pemeriksaan/${pemeriksaan.id}/peserta/create?jenis_peserta=${jenisPeserta}`"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="`/api/pemeriksaan/${pemeriksaan.id}/peserta/${jenisPeserta}`"
        ref="pageIndex"
        :on-toast="toast"
        :showImport="false"
        :showKelola="true"
        :kelolaUrl="`/pemeriksaan/${pemeriksaan.id}/peserta-parameter/kelola?jenis_peserta=${jenisPeserta}`"
        kelola-label="Pemetaan Nilai Peserta"
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
        <template #cell-parameter_peserta="{ row }">
            <div class="flex justify-center">
                <BadgeGroup
                    :badges="[
                        {
                            value: row.jumlah_parameter || 0,
                            colorClass: 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200',
                            onClick: () => router.visit(`/pemeriksaan/${pemeriksaan.id}/peserta/${row.id}/parameter?jenis_peserta=${jenisPeserta}`),
                        },
                    ]"
                />
            </div>
        </template>
    </PageIndex>
</template>
