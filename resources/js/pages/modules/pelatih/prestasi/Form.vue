<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    pelatihId: number | null;
    initialData?: Record<string, any>;
}>();

const formData = ref({
    pelatih_id: props.pelatihId || null,
    jenis_prestasi: props.initialData?.jenis_prestasi || 'individu',
    juara: props.initialData?.juara || '',
    medali: props.initialData?.medali || null,
    kategori_prestasi_pelatih_id: props.initialData?.kategori_prestasi_pelatih_id || null,
    nama_event: props.initialData?.nama_event || '',
    tingkat_id: props.initialData?.tingkat_id || null,
    tanggal: props.initialData?.tanggal || '',
    keterangan: props.initialData?.keterangan || '',
    bonus: props.initialData?.bonus || 0,
    id: props.initialData?.id || undefined,
    anggota_beregu: props.initialData?.anggota_beregu || [],
});

const tingkatOptions = ref<{ value: number; label: string }[]>([]);
const kategoriPrestasiPelatihOptions = ref<{ value: number; label: string }[]>([]);
const medaliOptions = [
    { value: 'Emas', label: 'Emas' },
    { value: 'Perak', label: 'Perak' },
    { value: 'Perunggu', label: 'Perunggu' },
];
const jenisPrestasiOptions = [
    { value: 'individu', label: 'Individu' },
    { value: 'ganda/mixed/beregu/double', label: 'Ganda/Mixed/Beregu/Double' },
];

// Modal untuk pilih anggota beregu
const showBereguModal = ref(false);
const pelatihBereguOptions = ref<{ id: number; nama: string; foto?: string }[]>([]);
const selectedBereguPelatihIds = ref<number[]>([]);
const loadingBereguPelatih = ref(false);
const searchBereguPelatih = ref('');

watch(
    () => props.initialData,
    (newVal) => {
        if (newVal) {
            Object.assign(formData.value, newVal);
            if (newVal.anggota_beregu && Array.isArray(newVal.anggota_beregu)) {
                selectedBereguPelatihIds.value = newVal.anggota_beregu.map((p: any) => p.id || p);
            }
        }
    },
    { immediate: true, deep: true },
);

watch(
    () => formData.value.jenis_prestasi,
    (newVal) => {
        if (newVal === 'ganda/mixed/beregu/double') {
            loadBereguPelatihOptions();
        } else {
            selectedBereguPelatihIds.value = [];
            formData.value.anggota_beregu = [];
        }
    },
    { immediate: true },
);

// Watch untuk field-updated event dari FormInput
const handleFieldUpdated = (data: { field: string; value: any }) => {
    if (data.field === 'jenis_prestasi') {
        formData.value.jenis_prestasi = data.value;
        if (data.value === 'ganda/mixed/beregu/double') {
            loadBereguPelatihOptions();
        } else {
            selectedBereguPelatihIds.value = [];
            formData.value.anggota_beregu = [];
        }
    }
};

onMounted(async () => {
    try {
        const res = await axios.get('/api/tingkat-list');
        tingkatOptions.value = res.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
    } catch (e) {
        console.error('Gagal mengambil data tingkat', e);
        toast({ title: 'Gagal memuat daftar tingkat', variant: 'destructive' });
        tingkatOptions.value = [];
    }

    try {
        const res = await axios.get('/api/kategori-prestasi-pelatih-list');
        kategoriPrestasiPelatihOptions.value = res.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
    } catch (e) {
        console.error('Gagal mengambil data kategori prestasi pelatih', e);
        toast({ title: 'Gagal memuat daftar kategori prestasi pelatih', variant: 'destructive' });
        kategoriPrestasiPelatihOptions.value = [];
    }

    if (formData.value.jenis_prestasi === 'ganda/mixed/beregu/double') {
        loadBereguPelatihOptions();
    }
});

const loadBereguPelatihOptions = async () => {
    if (!props.pelatihId) return;

    loadingBereguPelatih.value = true;
    try {
        // Ambil cabor_id dari pelatih
        const caborRes = await axios.get(`/api/pelatih/${props.pelatihId}/cabor`);
        const caborId = caborRes.data?.data?.cabor_id;

        if (!caborId) {
            toast({ title: 'Pelatih belum memiliki cabor', variant: 'destructive' });
            pelatihBereguOptions.value = [];
            return;
        }

        // Ambil semua pelatih dari cabor yang sama (kecuali pelatih yang sedang diinput)
        const pelatihListRes = await axios.get(`/api/pelatih`, {
            params: {
                cabor_id: caborId,
                per_page: -1,
            },
        });

        // Filter untuk exclude pelatih yang sedang diinput
        const filteredPelatih = (pelatihListRes.data.data || []).filter((item: any) => item.id !== props.pelatihId);

        pelatihBereguOptions.value = filteredPelatih.map((item: any) => ({
            id: item.id,
            nama: item.nama,
            foto: item.foto,
        }));
    } catch (e) {
        console.error('Gagal mengambil data pelatih beregu', e);
        toast({ title: 'Gagal memuat daftar pelatih beregu', variant: 'destructive' });
        pelatihBereguOptions.value = [];
    } finally {
        loadingBereguPelatih.value = false;
    }
};

const openBereguModal = () => {
    if (formData.value.jenis_prestasi !== 'ganda/mixed/beregu/double') {
        return;
    }
    showBereguModal.value = true;
    if (pelatihBereguOptions.value.length === 0) {
        loadBereguPelatihOptions();
    }
};

const closeBereguModal = () => {
    showBereguModal.value = false;
};

const toggleBereguPelatih = (pelatihId: number) => {
    const index = selectedBereguPelatihIds.value.indexOf(pelatihId);
    if (index > -1) {
        selectedBereguPelatihIds.value.splice(index, 1);
    } else {
        selectedBereguPelatihIds.value.push(pelatihId);
    }
};

const saveBereguSelection = () => {
    formData.value.anggota_beregu = selectedBereguPelatihIds.value;
    closeBereguModal();
    toast({ title: `${selectedBereguPelatihIds.value.length} pelatih dipilih`, variant: 'success' });
};

const filteredBereguPelatih = computed(() => {
    if (!searchBereguPelatih.value) {
        return pelatihBereguOptions.value;
    }
    const search = searchBereguPelatih.value.toLowerCase();
    return pelatihBereguOptions.value.filter((pelatih) => pelatih.nama.toLowerCase().includes(search));
});

const formInputs = computed(() => [
    { name: 'jenis_prestasi', label: 'Jenis Prestasi', type: 'select' as const, placeholder: 'Pilih Jenis Prestasi', options: jenisPrestasiOptions, required: true },
    {
        name: 'kategori_prestasi_pelatih_id',
        label: 'Kategori Prestasi Pelatih',
        type: 'select' as const,
        placeholder: 'Pilih Kategori Prestasi Pelatih',
        options: kategoriPrestasiPelatihOptions.value,
    },
    { name: 'nama_event', label: 'Nama Event', type: 'text' as const, placeholder: 'Masukkan nama event', required: true },
    { name: 'tingkat_id', label: 'Tingkat', type: 'select' as const, placeholder: 'Pilih Tingkat', options: tingkatOptions.value },
    { name: 'tanggal', label: 'Tanggal', type: 'date' as const, placeholder: 'Pilih tanggal', required: false },
    { name: 'juara', label: 'Juara', type: 'text' as const, placeholder: 'Masukkan juara (misal: Juara 1)' },
    { name: 'medali', label: 'Medali', type: 'select' as const, placeholder: 'Pilih Medali', options: medaliOptions },
    { name: 'keterangan', label: 'Keterangan', type: 'textarea' as const, placeholder: 'Masukkan keterangan' },
    { 
        name: 'bonus', 
        label: 'Bonus (Rupiah)', 
        type: 'number' as const, 
        placeholder: 'Masukkan jumlah bonus', 
        required: false,
        help: 'Masukkan jumlah bonus dalam rupiah (contoh: 1000000 untuk 1 juta)',
    },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    if (!props.pelatihId) {
        toast({ title: 'ID Pelatih tidak ditemukan', variant: 'destructive' });
        return;
    }

    const formFields = { ...formData.value, ...dataFromFormInput };
    
    // Jika beregu, kirim anggota_beregu
    if (formFields.jenis_prestasi === 'ganda/mixed/beregu/double') {
        formFields.anggota_beregu = selectedBereguPelatihIds.value;
    } else {
        formFields.anggota_beregu = [];
    }

    const url = `/pelatih/${props.pelatihId}/prestasi`;

    save(formFields, {
        url: url,
        mode: props.mode,
        id: formData.value.id,
        successMessage: props.mode === 'create' ? 'Prestasi berhasil ditambahkan!' : 'Prestasi berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan prestasi.' : 'Gagal memperbarui prestasi.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        onSuccess: () => {
            router.visit(`/pelatih/${props.pelatihId}/prestasi`);
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" @field-updated="handleFieldUpdated" />
        
        <!-- Button untuk pilih anggota beregu -->
        <div v-if="formData.jenis_prestasi === 'ganda/mixed/beregu/double'" class="mt-4">
            <Button type="button" variant="outline" @click="openBereguModal">
                Pilih Anggota Beregu
                <span v-if="selectedBereguPelatihIds.length > 0" class="ml-2 rounded-full bg-primary px-2 py-1 text-xs text-primary-foreground">
                    {{ selectedBereguPelatihIds.length }}
                </span>
            </Button>
            <p v-if="selectedBereguPelatihIds.length > 0" class="mt-2 text-sm text-muted-foreground">
                {{ selectedBereguPelatihIds.length }} pelatih dipilih
            </p>
        </div>

        <!-- Modal Pilih Anggota Beregu -->
        <Dialog v-model:open="showBereguModal">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Pilih Anggota Beregu</DialogTitle>
                    <DialogDescription>
                        Pilih pelatih yang sama-sama meraih prestasi ini. Semua pelatih yang dipilih akan memiliki prestasi yang sama.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="space-y-4">
                    <!-- Search -->
                    <div>
                        <Input
                            v-model="searchBereguPelatih"
                            placeholder="Cari pelatih..."
                            class="w-full"
                        />
                    </div>

                    <!-- List Pelatih -->
                    <div v-if="loadingBereguPelatih" class="flex items-center justify-center py-8">
                        <p class="text-muted-foreground">Memuat data...</p>
                    </div>
                    <div v-else-if="filteredBereguPelatih.length === 0" class="flex items-center justify-center py-8">
                        <p class="text-muted-foreground">Tidak ada pelatih yang tersedia</p>
                    </div>
                    <div v-else class="max-h-96 space-y-2 overflow-y-auto">
                        <div
                            v-for="pelatih in filteredBereguPelatih"
                            :key="pelatih.id"
                            class="flex items-center space-x-3 rounded-lg border p-3 hover:bg-accent"
                        >
                            <Checkbox
                                :checked="selectedBereguPelatihIds.includes(pelatih.id)"
                                @update:checked="toggleBereguPelatih(pelatih.id)"
                            />
                            <div class="flex-1">
                                <Label class="cursor-pointer font-medium">{{ pelatih.nama }}</Label>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeBereguModal">Batal</Button>
                    <Button @click="saveBereguSelection">Simpan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
