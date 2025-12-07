<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { Loader2, UserCircle2, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const caborOptions = ref<{ value: number; label: string }[]>([]);
const kategoriOptions = ref<{ value: number; label: string }[]>([]);

const selectedCaborId = ref(props.initialData?.cabor_id || '');
const selectedKategoriId = ref(props.initialData?.cabor_kategori_id || '');

// State untuk peserta yang akan auto-load
const pesertaPreview = ref<{
    atlet: { id: number; nama: string; posisi?: string }[];
    pelatih: { id: number; nama: string; jenis?: string }[];
    tenagaPendukung: { id: number; nama: string; jenis?: string }[];
}>({
    atlet: [],
    pelatih: [],
    tenagaPendukung: [],
});
const loadingPeserta = ref(false);

const fetchCaborOptions = async () => {
    const res = await axios.get('/api/cabor-list');
    caborOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

const fetchKategoriOptions = async (caborId: number | string) => {
    if (!caborId) {
        kategoriOptions.value = [];
        return;
    }
    const res = await axios.get(`/api/cabor-kategori-by-cabor/${caborId}`);
    kategoriOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

// Fetch peserta berdasarkan cabor kategori
const fetchPesertaByKategori = async (kategoriId: number | string) => {
    if (!kategoriId) {
        pesertaPreview.value = { atlet: [], pelatih: [], tenagaPendukung: [] };
        return;
    }

    loadingPeserta.value = true;
    try {
        const res = await axios.get(`/api/pemeriksaan-khusus/peserta-by-cabor-kategori/${kategoriId}`);
        pesertaPreview.value = res.data || { atlet: [], pelatih: [], tenagaPendukung: [] };
    } catch (error) {
        console.error('Error fetching peserta:', error);
        pesertaPreview.value = { atlet: [], pelatih: [], tenagaPendukung: [] };
    } finally {
        loadingPeserta.value = false;
    }
};

// Watch untuk auto-load peserta ketika kategori berubah
watch(selectedKategoriId, (newVal) => {
    if (newVal && props.mode === 'create') {
        fetchPesertaByKategori(newVal);
    }
});

onMounted(async () => {
    await fetchCaborOptions();
    if (selectedCaborId.value) {
        await fetchKategoriOptions(selectedCaborId.value);
    }
    if (selectedKategoriId.value && props.mode === 'create') {
        await fetchPesertaByKategori(selectedKategoriId.value);
    }
});

function handleFieldUpdate({ field, value }: { field: string; value: any }) {
    if (field === 'cabor_id') {
        selectedCaborId.value = value;
        selectedKategoriId.value = '';
        pesertaPreview.value = { atlet: [], pelatih: [], tenagaPendukung: [] };
        fetchKategoriOptions(value);
    }
    if (field === 'cabor_kategori_id') {
        selectedKategoriId.value = value;
    }
}

const formInitialData = computed(() => ({
    cabor_id: selectedCaborId.value,
    cabor_kategori_id: selectedKategoriId.value,
    nama_pemeriksaan: props.initialData?.nama_pemeriksaan || '',
    tanggal_pemeriksaan: props.initialData?.tanggal_pemeriksaan || '',
    status: props.initialData?.status || 'belum',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'cabor_id',
        label: 'Cabor',
        type: 'select' as const,
        options: caborOptions.value,
        placeholder: 'Pilih Cabor',
        required: true,
        modelValue: selectedCaborId.value,
        onUpdateModelValue: (val: any) => handleFieldUpdate({ field: 'cabor_id', value: val }),
    },
    {
        name: 'cabor_kategori_id',
        label: 'Kategori',
        type: 'select' as const,
        options: kategoriOptions.value,
        placeholder: selectedCaborId.value ? 'Pilih Kategori' : 'Pilih cabor terlebih dahulu',
        required: true,
        disabled: !selectedCaborId.value,
        modelValue: selectedKategoriId.value,
        onUpdateModelValue: (val: any) => handleFieldUpdate({ field: 'cabor_kategori_id', value: val }),
    },
    {
        name: 'nama_pemeriksaan',
        label: 'Nama Pemeriksaan',
        type: 'text' as const,
        required: true,
    },
    {
        name: 'tanggal_pemeriksaan',
        label: 'Tanggal Pemeriksaan',
        type: 'date' as const,
        required: true,
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select' as const,
        required: true,
        options: [
            { value: 'belum', label: 'Belum' },
            { value: 'sebagian', label: 'Sebagian' },
            { value: 'selesai', label: 'Selesai' },
        ],
    },
]);

// Hitung total peserta
const totalPeserta = computed(() => {
    return (
        pesertaPreview.value.atlet.length +
        pesertaPreview.value.pelatih.length +
        pesertaPreview.value.tenagaPendukung.length
    );
});

const handleSave = (form: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const dataToSave: Record<string, any> = {
        ...form,
        cabor_id: selectedCaborId.value,
        cabor_kategori_id: selectedKategoriId.value,
    };
    
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    
    save(dataToSave, {
        url: '/pemeriksaan-khusus',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Pemeriksaan khusus berhasil ditambahkan' : 'Pemeriksaan khusus berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan pemeriksaan khusus' : 'Gagal memperbarui pemeriksaan khusus',
        redirectUrl: '/pemeriksaan-khusus',
        onError: setFormErrors,
    });
};
</script>

<template>
    <div class="space-y-6">
        <FormInput
            :form-inputs="formInputs"
            :initial-data="formInitialData"
            @save="handleSave"
            @field-updated="handleFieldUpdate"
            :disable-auto-reset="props.mode === 'create'"
            :hide-buttons="false"
        />

        <!-- Preview Peserta yang akan ditambahkan (hanya tampil saat create) -->
        <Card v-if="props.mode === 'create' && selectedKategoriId" class="mt-6">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    Peserta yang akan ditambahkan
                    <Badge variant="secondary" class="ml-2">{{ totalPeserta }} peserta</Badge>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <!-- Loading state -->
                <div v-if="loadingPeserta" class="flex items-center justify-center py-8">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    <span class="ml-2 text-muted-foreground">Memuat peserta...</span>
                </div>

                <!-- Empty state -->
                <div v-else-if="totalPeserta === 0" class="py-8 text-center text-muted-foreground">
                    Tidak ada peserta aktif di kategori ini
                </div>

                <!-- Peserta list -->
                <div v-else class="space-y-4">
                    <!-- Atlet -->
                    <div v-if="pesertaPreview.atlet.length > 0">
                        <h4 class="mb-2 font-medium text-sm flex items-center gap-2">
                            <UserCircle2 class="h-4 w-4 text-blue-500" />
                            Atlet
                            <Badge variant="outline">{{ pesertaPreview.atlet.length }}</Badge>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="atlet in pesertaPreview.atlet" :key="atlet.id" variant="secondary">
                                {{ atlet.nama }}
                                <span v-if="atlet.posisi" class="text-muted-foreground ml-1">({{ atlet.posisi }})</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Pelatih -->
                    <div v-if="pesertaPreview.pelatih.length > 0">
                        <h4 class="mb-2 font-medium text-sm flex items-center gap-2">
                            <UserCircle2 class="h-4 w-4 text-green-500" />
                            Pelatih
                            <Badge variant="outline">{{ pesertaPreview.pelatih.length }}</Badge>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="pelatih in pesertaPreview.pelatih" :key="pelatih.id" variant="secondary">
                                {{ pelatih.nama }}
                                <span v-if="pelatih.jenis" class="text-muted-foreground ml-1">({{ pelatih.jenis }})</span>
                            </Badge>
                        </div>
                    </div>

                    <!-- Tenaga Pendukung -->
                    <div v-if="pesertaPreview.tenagaPendukung.length > 0">
                        <h4 class="mb-2 font-medium text-sm flex items-center gap-2">
                            <UserCircle2 class="h-4 w-4 text-orange-500" />
                            Tenaga Pendukung
                            <Badge variant="outline">{{ pesertaPreview.tenagaPendukung.length }}</Badge>
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <Badge v-for="tenaga in pesertaPreview.tenagaPendukung" :key="tenaga.id" variant="secondary">
                                {{ tenaga.nama }}
                                <span v-if="tenaga.jenis" class="text-muted-foreground ml-1">({{ tenaga.jenis }})</span>
                            </Badge>
                        </div>
                    </div>
                </div>

                <p class="mt-4 text-xs text-muted-foreground">
                    * Semua peserta aktif dari kategori ini akan otomatis ditambahkan ke pemeriksaan khusus
                </p>
            </CardContent>
        </Card>
    </div>
</template>

