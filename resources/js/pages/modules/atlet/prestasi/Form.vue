<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import SelectTableMultiple from '@/pages/modules/components/SelectTableMultiple.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    atletId: number | null;
    mode: 'create' | 'edit';
    initialData?: any;
    redirectUrl?: string;
}>();

const formData = ref<Record<string, any>>({
    id: props.initialData?.id || undefined,
    atlet_id: props.atletId,
    jenis_prestasi: props.initialData?.jenis_prestasi || 'individu',
    juara: props.initialData?.juara || '',
    medali: props.initialData?.medali || null,
    nama_event: props.initialData?.nama_event || '',
    tingkat_id: props.initialData?.tingkat_id || null,
    tanggal: props.initialData?.tanggal || '',
    keterangan: props.initialData?.keterangan || '',
    bonus: props.initialData?.bonus || 0,
    anggota_beregu: props.initialData?.anggota_beregu || [],
});

const formInputInitialData = computed(() => ({ ...formData.value }));

const tingkatOptions = ref<{ value: number; label: string }[]>([]);
const medaliOptions = [
    { value: 'Emas', label: 'Emas' },
    { value: 'Perak', label: 'Perak' },
    { value: 'Perunggu', label: 'Perunggu' },
];
const jenisPrestasiOptions = [
    { value: 'individu', label: 'Individu' },
    { value: 'ganda/mixed/beregu/double', label: 'Ganda/Mixed/Beregu/Double' },
];

// Modal untuk pilih anggota beregu (muncul setelah klik Save)
const showBereguModal = ref(false);
const selectedBereguAtletIds = ref<number[]>([]);
const isSaving = ref(false);
const pendingFormData = ref<any>(null);
const pendingSetFormErrors = ref<((errors: Record<string, string>) => void) | null>(null);
const bereguEndpoint = ref<string>('');

watch(
    () => props.initialData,
    (newVal) => {
        if (newVal) {
            Object.assign(formData.value, newVal);
            if (props.atletId) {
                formData.value.atlet_id = props.atletId;
            }
            if (newVal.anggota_beregu && Array.isArray(newVal.anggota_beregu)) {
                selectedBereguAtletIds.value = newVal.anggota_beregu.map((a: any) => a.id || a);
            }
        }
    },
    { immediate: true, deep: true },
);

// Watch untuk field-updated event dari FormInput
const handleFieldUpdated = (data: { field: string; value: any }) => {
    if (data.field === 'jenis_prestasi') {
        formData.value.jenis_prestasi = data.value;
        if (data.value !== 'ganda/mixed/beregu/double') {
            selectedBereguAtletIds.value = [];
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
});

// Setup endpoint untuk SelectTableMultiple
const setupBereguEndpoint = async () => {
    if (!props.atletId) return;
    
    // Gunakan endpoint khusus untuk beregu
    bereguEndpoint.value = `/api/atlet/${props.atletId}/beregu/available`;
};

const openBereguModal = async () => {
    if (!props.atletId) {
        toast({ title: 'Atlet ID tidak ditemukan', variant: 'destructive' });
        return;
    }
    
    // Setup endpoint jika belum ada
    if (!bereguEndpoint.value) {
        await setupBereguEndpoint();
    }
    
    // Reset selected jika create mode, atau load dari initialData jika edit mode
    if (!formData.value.id) {
        selectedBereguAtletIds.value = [];
    } else if (formData.value.anggota_beregu && Array.isArray(formData.value.anggota_beregu)) {
        selectedBereguAtletIds.value = formData.value.anggota_beregu.map((a: any) => a.id || a);
    }
    
    showBereguModal.value = true;
};

const closeBereguModal = () => {
    showBereguModal.value = false;
    pendingFormData.value = null;
    pendingSetFormErrors.value = null;
};

const handleSaveBeregu = () => {
    if (selectedBereguAtletIds.value.length === 0) {
        toast({ 
            title: 'Pilih minimal 1 atlet untuk beregu', 
            variant: 'destructive' 
        });
        return;
    }
    
    // Lanjutkan save dengan data yang sudah disiapkan
    if (pendingFormData.value && pendingSetFormErrors.value) {
        const formFields = { ...pendingFormData.value };
        formFields.anggota_beregu = selectedBereguAtletIds.value;
        
        const baseUrl = `/atlet/${props.atletId}/prestasi`;
        save(formFields, {
            url: baseUrl,
            mode: formData.value.id ? 'edit' : 'create',
            id: formData.value.id,
            successMessage: formData.value.id ? 'Prestasi berhasil diperbarui!' : 'Prestasi berhasil ditambahkan!',
            errorMessage: formData.value.id ? 'Gagal memperbarui prestasi.' : 'Gagal menambah prestasi.',
            onError: (errors: Record<string, string>) => {
                pendingSetFormErrors.value?.(errors);
            },
            redirectUrl: props.redirectUrl ?? `/atlet/${props.atletId}/prestasi`,
        });
    }
    
    closeBereguModal();
};

// Columns untuk SelectTableMultiple
const getBereguColumns = () => [
    { key: 'nama', label: 'Nama Atlet' },
    { key: 'jenis_kelamin', label: 'Jenis Kelamin', format: (row: any) => row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-' },
    { key: 'usia', label: 'Usia', format: (row: any) => row.usia ? `${row.usia} tahun` : '-' },
];

const formInputs = computed(() => [
    { name: 'jenis_prestasi', label: 'Jenis Prestasi', type: 'select' as const, placeholder: 'Pilih Jenis Prestasi', options: jenisPrestasiOptions, required: true },
    { name: 'nama_event', label: 'Nama Event', type: 'text' as const, placeholder: 'Masukkan nama event', required: true },
    { name: 'tingkat_id', label: 'Tingkat', type: 'select' as const, placeholder: 'Pilih Tingkat', options: tingkatOptions.value },
    { name: 'tanggal', label: 'Tanggal', type: 'date' as const, placeholder: 'Pilih tanggal' },
    { name: 'juara', label: 'Juara', type: 'text' as const, placeholder: 'Masukkan juara (misal: Juara 1)' },
    { name: 'medali', label: 'Medali', type: 'select' as const, placeholder: 'Pilih Medali', options: medaliOptions },
    { name: 'keterangan', label: 'Keterangan', type: 'textarea' as const, placeholder: 'Masukkan keterangan tambahan (opsional)' },
    { 
        name: 'bonus', 
        label: 'Bonus (Rupiah)', 
        type: 'number' as const, 
        placeholder: 'Masukkan jumlah bonus', 
        required: false,
        help: 'Masukkan jumlah bonus dalam rupiah (contoh: 1000000 untuk 1 juta)',
    },
]);

const handleSave = async (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };
    if (props.atletId && !formFields.atlet_id) {
        formFields.atlet_id = props.atletId;
    }
    
    // Jika beregu, tampilkan modal untuk pilih anggota
    if (formFields.jenis_prestasi === 'ganda/mixed/beregu/double') {
        // Simpan data sementara
        pendingFormData.value = formFields;
        pendingSetFormErrors.value = setFormErrors;
        
        // Setup endpoint dan buka modal
        await setupBereguEndpoint();
        openBereguModal();
        return; // Jangan lanjutkan save, tunggu user pilih anggota di modal
    } else {
        // Jika individu, langsung save
        formFields.anggota_beregu = [];
    }

    const baseUrl = `/atlet/${props.atletId}/prestasi`;
    save(formFields, {
        url: baseUrl,
        mode: formData.value.id ? 'edit' : 'create',
        id: formData.value.id,
        successMessage: formData.value.id ? 'Prestasi berhasil diperbarui!' : 'Prestasi berhasil ditambahkan!',
        errorMessage: formData.value.id ? 'Gagal memperbarui prestasi.' : 'Gagal menambah prestasi.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        redirectUrl: props.redirectUrl ?? `/atlet/${props.atletId}/prestasi`,
    });
};
</script>

<template>
    <div class="space-y-6">
        <FormInput 
            :form-inputs="formInputs" 
            :initial-data="formInputInitialData" 
            @save="handleSave" 
            @field-updated="handleFieldUpdated"
        />

        <!-- Modal Pilih Anggota Beregu (muncul setelah klik Save) -->
        <Dialog :open="showBereguModal" @update:open="showBereguModal = $event">
            <DialogContent class="max-h-[80vh] max-w-5xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-xl font-semibold">
                        Pilih Anggota Beregu
                    </DialogTitle>
                </DialogHeader>

                <div class="mt-4">
                    <p class="mb-4 text-sm text-muted-foreground">
                        Pilih atlet yang sama-sama meraih prestasi ini. Semua atlet yang dipilih akan memiliki prestasi yang sama.
                    </p>
                    <SelectTableMultiple
                        v-if="bereguEndpoint"
                        :label="'Pilih Atlet'"
                        :endpoint="bereguEndpoint"
                        :columns="getBereguColumns()"
                        :id-key="'id'"
                        :name-key="'nama'"
                        v-model:selected-ids="selectedBereguAtletIds"
                    />
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="closeBereguModal" :disabled="isSaving">
                        Batal
                    </Button>
                    <Button @click="handleSaveBeregu" :disabled="isSaving || selectedBereguAtletIds.length === 0">
                        {{ isSaving ? 'Menyimpan...' : `Simpan (${selectedBereguAtletIds.length} atlet dipilih)` }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
