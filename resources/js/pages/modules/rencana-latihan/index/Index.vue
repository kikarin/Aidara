<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const page = usePage();
const programId = page.props.program_id as string;
const rencanaId = page.props.rencana_id as string;
const jenisPeserta = page.props.jenis_peserta as string;
const infoHeader = page.props.infoHeader as any;
const info = computed(() => infoHeader || {});
const infoRencana = page.props.infoRencana as any;
const keterangan = ref('');

const jenisLabel: Record<string, string> = {
    atlet: 'Atlet',
    pelatih: 'Pelatih',
    'tenaga-pendukung': 'Tenaga Pendukung',
};

const breadcrumbs = [
    { title: 'Program Latihan', href: `/program-latihan` },
    { title: 'Rencana Latihan', href: `/program-latihan/${programId}/rencana-latihan` },
    { title: `Peserta (${jenisLabel[jenisPeserta] || jenisPeserta})`, href: '#' },
];

const columns = computed(() => {
    const baseColumns = [
        { key: 'nama', label: 'Nama', orderable: false },
        { key: 'kehadiran', label: 'Kehadiran', format: (row: any) => row.kehadiran || '-', orderable: false },
        {
            key: 'keterangan',
            label: 'Keterangan',
            format: (row: any) => row.keterangan || '-',
            orderable: false,
        },
        {
            key: 'foto_kehadiran',
            label: 'Foto Kehadiran',
            orderable: false,
            format: (row: any) => {
                if (row.foto_kehadiran) {
                    return `<div class='cursor-pointer' onclick="window.open('${row.foto_kehadiran}', '_blank')">
          <img src='${row.foto_kehadiran}' alt='Foto Kehadiran ${row.nama}' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' />
        </div>`;
                }
                return '<div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">-</div>';
            },
        },
        {
            key: 'jenis_kelamin',
            label: 'Jenis Kelamin',
            format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
            orderable: false,
        },
        { key: 'tempat_lahir', label: 'Tempat Lahir', orderable: false },
        {
            key: 'tanggal_lahir',
            label: 'Tanggal Lahir',
            orderable: false,
            format: (row: any) =>
                row.tanggal_lahir
                    ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
                    : '-',
        },
        { key: 'no_hp', label: 'No HP', orderable: false },
        {
            key: 'kategori_is_active',
            label: 'Status',
            orderable: false,
            format: (row: any) =>
                row.kategori_is_active == 1
                    ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Aktif</span>'
                    : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Nonaktif</span>',
        },
    ];

    // Tambahkan kolom spesifik sesuai jenis peserta
    if (jenisPeserta === 'atlet') {
        return [
            { key: 'nama', label: 'Nama', orderable: false },
            { key: 'posisi_atlet_nama', label: 'Posisi', format: (row: any) => row.posisi_atlet_nama || '-', orderable: false },
            ...baseColumns.slice(1), // Skip nama karena sudah ada di atas
        ];
    } else if (jenisPeserta === 'pelatih') {
        return [
            { key: 'nama', label: 'Nama', orderable: false },
            { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih', format: (row: any) => row.jenis_pelatih_nama || '-', orderable: false },
            ...baseColumns.slice(1), // Skip nama karena sudah ada di atas
        ];
    } else if (jenisPeserta === 'tenaga-pendukung') {
        return [
            { key: 'nama', label: 'Nama', orderable: false },
            {
                key: 'jenis_tenaga_pendukung_nama',
                label: 'Jenis Tenaga Pendukung',
                format: (row: any) => row.jenis_tenaga_pendukung_nama || '-',
                orderable: false,
            },
            ...baseColumns.slice(1), // Skip nama karena sudah ada di atas
        ];
    }

    return baseColumns;
});

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const showConfirmKehadiran = ref(false);
const kehadiranToSet = ref('');

const actions = (row: any) => [
    {
        label: 'Set Kehadiran',
        onClick: () => router.visit(`/program-latihan/${programId}/rencana-latihan/${rencanaId}/set-kehadiran/${jenisPeserta}/${row.id}`),
        permission: `Rencana Latihan Set Kehadiran`,
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        variant: 'destructive',
        permission: `Rencana Latihan Delete`,
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        await axios.post(`/api/rencana-latihan/${rencanaId}/peserta/${jenisPeserta}/destroy-selected`, {
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
        await axios.delete(`/api/rencana-latihan/${rencanaId}/peserta/${jenisPeserta}/${row.id}`);
        toast({ title: 'Data berhasil dihapus', variant: 'success' });
        if (pageIndex.value.fetchData) pageIndex.value.fetchData();
    } catch {
        toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
    }
};

const setKehadiran = async (status: string) => {
    if (!selected.value.length) return;
    try {
        await axios.post(`/rencana-latihan/${rencanaId}/peserta/${jenisPeserta}/set-kehadiran`, {
            ids: selected.value,
            kehadiran: status,
            keterangan: keterangan.value,
        });
        toast({ title: `Kehadiran Peserta berhasil diupdate menjadi '${status}'`, variant: 'success' });
        selected.value = [];
        keterangan.value = '';
        if (pageIndex.value.fetchData) pageIndex.value.fetchData();
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal update kehadiran', variant: 'destructive' });
    }
};

const handleSetKehadiran = (status: string) => {
    kehadiranToSet.value = status;
    showConfirmKehadiran.value = true;
};

const confirmSetKehadiran = async () => {
    await setKehadiran(kehadiranToSet.value);
    showConfirmKehadiran.value = false;
    kehadiranToSet.value = '';
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            :title="`Peserta (${jenisLabel[jenisPeserta] || jenisPeserta})`"
            module-name="Rencana Latihan"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            :on-delete-row="deleteRow"
            :show-import="false"
            :create-url="''"
            :api-endpoint="`/api/rencana-latihan/${rencanaId}/peserta/${jenisPeserta}`"
            ref="pageIndex"
            :disable-length="true"
            :hide-search="false"
            :hide-pagination="true"
            :on-toast="toast"
            :showKehadiran="true"
            :showKelola="true"
            :kelolaUrl="`/program-latihan/${programId}/rencana-latihan/${rencanaId}/kelola/${jenisPeserta}`"
            @setKehadiran="handleSetKehadiran"
            kelola-label="Pemetaan Nilai Peserta"
        >
            <template #header-extra>
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Kolom kiri: Informasi Program Latihan -->
                    <div class="bg-card rounded-lg border p-4">
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
                    <!-- Kolom kanan: Informasi Rencana Latihan -->
                    <div class="bg-card rounded-lg border p-4">
                        <h3 class="mb-2 text-lg font-semibold">Informasi Rencana Latihan</h3>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Tanggal:</span>
                                <span class="text-sm font-medium">{{
                                    infoRencana.tanggal ? new Date(infoRencana.tanggal).toLocaleDateString('id-ID') : '-'
                                }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Materi:</span>
                                <span class="text-sm font-medium">{{ infoRencana.materi }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Lokasi:</span>
                                <span class="text-sm font-medium">{{ infoRencana.lokasi_latihan }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Target Latihan:</span>
                                <span class="text-sm font-medium">{{
                                    infoRencana.target_latihan && infoRencana.target_latihan.length ? infoRencana.target_latihan.join(', ') : '-'
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </PageIndex>
        <Dialog v-model:open="showConfirmKehadiran">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Set Kehadiran</DialogTitle>
                    <DialogDescription class="space-y-4">
                        <div>
                            Set status kehadiran Peserta menjadi <b>{{ kehadiranToSet }}</b> untuk {{ selected.length }} peserta terpilih?
                        </div>

                        <div>
                            <label for="keterangan" class="mb-1 block text-sm font-medium">Keterangan (opsional)</label>
                            <textarea
                                id="keterangan"
                                v-model="keterangan"
                                rows="3"
                                class="w-full rounded border p-2 text-sm"
                                placeholder="Contoh: Cedera, Izin ke dokter, dll"
                            />
                        </div>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button class="text-sm font-medium" @click="showConfirmKehadiran = false">Batal</Button>
                    <Button variant="default" @click="confirmSetKehadiran">Ya, Set Kehadiran</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
