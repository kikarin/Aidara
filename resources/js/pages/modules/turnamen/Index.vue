<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Calendar } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const breadcrumbs = [{ title: 'Turnamen', href: '/turnamen' }];

const columns = [
    { key: 'nama', label: 'Nama Turnamen' },
    {
        key: 'foto',
        label: 'Foto',
        orderable: false,
        format: (row: any) => {
            if (row.foto_url) {
                return `<div class="cursor-pointer" onclick="window.open('${row.foto_url}', '_blank')">
                    <img src="${row.foto_url}" alt="Foto ${row.nama}" class="h-16 w-16 rounded object-cover border hover:shadow-md transition-shadow" />
                </div>`;
            }
            return '<div class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-xs">-</div>';
        },
    },
    { key: 'cabor_kategori_nama', label: 'Cabor Kategori', orderable: false },
    {
        key: 'tanggal_mulai',
        label: 'Tanggal Mulai',
        format: (row: any) => {
            if (!row.tanggal_mulai) return '-';
            const date = new Date(row.tanggal_mulai);
            const options: Intl.DateTimeFormatOptions = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
            return date.toLocaleDateString('id-ID', options);
        },
    },
    {
        key: 'tanggal_selesai',
        label: 'Tanggal Selesai',
        format: (row: any) => {
            if (!row.tanggal_selesai) return '-';
            const date = new Date(row.tanggal_selesai);
            const options: Intl.DateTimeFormatOptions = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
            return date.toLocaleDateString('id-ID', options);
        },
    },
    { key: 'tingkat_nama', label: 'Tingkat', orderable: false },
    { key: 'lokasi', label: 'Lokasi' },
    { key: 'juara_nama', label: 'Juara', orderable: false },
    { key: 'hasil', label: 'Hasil', orderable: false },
    {
        key: 'peserta',
        label: 'Peserta',
        orderable: false,
    },
];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/turnamen/${row.id}`),
        permission: 'Turnamen Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/turnamen/${row.id}/edit`),
        permission: 'Turnamen Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Turnamen Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/turnamen/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message,
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);

        const message = error.response?.data?.message;
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const deleteRow = async (row: any) => {
    await router.delete(`/turnamen/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};

// =====================
// Filter State & Options
// =====================
const showFilterModal = ref(false);
const currentFilters = ref<any>({});
const caborKategoriList = ref<Array<{ id: number; nama: string }>>([]);
const tingkatList = ref<Array<{ id: number; nama: string }>>([]);
const juaraList = ref<Array<{ id: number; nama: string }>>([]);

onMounted(async () => {
    try {
        const [kategoriRes, tingkatRes, juaraRes] = await Promise.all([
            // Ambil kategori beserta cabor_nama via API index (per_page=-1)
            axios.get('/api/cabor-kategori', { params: { per_page: -1 } }),
            axios.get('/api/tingkat-list'),
            axios.get('/api/juara-list'),
        ]);
        // Response kategori: { data: [...], meta: {...} }
        const kategoriData = kategoriRes.data && kategoriRes.data.data ? kategoriRes.data.data : [];
        // Normalisasikan agar memiliki {id, nama, cabor_nama}
        caborKategoriList.value = kategoriData.map((k: any) => ({ id: k.id, nama: k.nama, cabor_nama: k.cabor_nama }));
        tingkatList.value = tingkatRes.data || [];
        juaraList.value = juaraRes.data || [];
    } catch  {
        // silently fail; user still can filter by tanggal tanpa options
    }
});

const bukaFilterModal = () => {
    showFilterModal.value = true;
};

const handleFilter = (filters: any) => {
    currentFilters.value = filters;
    pageIndex.value.handleFilterFromParent(filters);
    toast({ title: 'Filter berhasil diterapkan', variant: 'success' });
    showFilterModal.value = false;
};

const resetFilters = () => {
    currentFilters.value = {};
};

const triggerDatePicker = (fieldId: string) => {
    const dateInput = document.getElementById(fieldId) as HTMLInputElement | null;
    if (dateInput && (dateInput as any).showPicker) {
        (dateInput as any).showPicker();
    }
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Turnamen"
            module-name="Turnamen"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/turnamen/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/turnamen"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
            :showFilter="true"
            @filter="bukaFilterModal"
        >
            <template #cell-peserta="{ row }">
                <BadgeGroup
                    :badges="[
                        {
                            label: 'Atlet',
                            value: row.peserta_counts?.atlet || 0,
                            colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                            onClick: () => router.visit(`/turnamen/${row.id}/peserta?jenis_peserta=atlet`),
                        },
                        {
                            label: 'Pelatih',
                            value: row.peserta_counts?.pelatih || 0,
                            colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                            onClick: () => router.visit(`/turnamen/${row.id}/peserta?jenis_peserta=pelatih`),
                        },
                        {
                            label: 'Tenaga Pendukung',
                            value: row.peserta_counts?.tenaga_pendukung || 0,
                            colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            onClick: () => router.visit(`/turnamen/${row.id}/peserta?jenis_peserta=tenaga-pendukung`),
                        },
                    ]"
                />
            </template>
        </PageIndex>

        <!-- Filter Modal -->
        <Dialog v-model:open="showFilterModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Filter Data Turnamen</DialogTitle>
                    <DialogDescription> Terapkan filter untuk menyaring data turnamen. </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="filter_start_date">Tanggal Mulai</Label>
                            <div class="relative">
                                <Input
                                    id="filter_start_date"
                                    v-model="currentFilters.filter_start_date"
                                    type="date"
                                    class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden"
                                />
                                <div
                                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                    @click="() => triggerDatePicker('filter_start_date')"
                                >
                                    <Calendar class="text-muted-foreground h-4 w-4" />
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="filter_end_date">Tanggal Selesai</Label>
                            <div class="relative">
                                <Input
                                    id="filter_end_date"
                                    v-model="currentFilters.filter_end_date"
                                    type="date"
                                    class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden"
                                />
                                <div
                                    class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                    @click="() => triggerDatePicker('filter_end_date')"
                                >
                                    <Calendar class="text-muted-foreground h-4 w-4" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="cabor_kategori_id">Cabor Kategori</Label>
                            <Select v-model="currentFilters.cabor_kategori_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Cabor Kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="undefined">Semua</SelectItem>
                                    <SelectItem v-for="opt in caborKategoriList" :key="opt.id" :value="opt.id.toString()">
                                        {{ (opt.cabor_nama ? opt.cabor_nama + ' - ' : '') + (opt.nama || '-') }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="tingkat_id">Tingkat</Label>
                            <Select v-model="currentFilters.tingkat_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Tingkat" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="undefined">Semua</SelectItem>
                                    <SelectItem v-for="opt in tingkatList" :key="opt.id" :value="opt.id.toString()">{{ opt.nama }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="juara_id">Juara</Label>
                            <Select v-model="currentFilters.juara_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Juara" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="undefined">Semua</SelectItem>
                                    <SelectItem v-for="opt in juaraList" :key="opt.id" :value="opt.id.toString()">{{ opt.nama }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="resetFilters"> Reset </Button>
                    <Button @click="handleFilter(currentFilters)"> Terapkan Filter </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
