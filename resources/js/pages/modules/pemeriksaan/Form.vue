<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const caborOptions = ref<{ value: number; label: string }[]>([]);
const kategoriOptions = ref<{ value: number; label: string }[]>([]);
const tenagaPendukungOptions = ref<{ value: number; label: string }[]>([]);

const selectedCaborId = ref(props.initialData?.cabor_id || '');
const selectedKategoriId = ref(props.initialData?.cabor_kategori_id || '');
const selectedTenagaPendukungId = ref(props.initialData?.tenaga_pendukung_id || '');

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
const fetchTenagaPendukungOptions = async () => {
    const res = await axios.get('/api/tenaga-pendukung', { params: { per_page: -1 } });
    tenagaPendukungOptions.value = (res.data.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

onMounted(async () => {
    await fetchCaborOptions();
    await fetchTenagaPendukungOptions();
    if (selectedCaborId.value) await fetchKategoriOptions(selectedCaborId.value);
});

function handleFieldUpdate({ field, value }: { field: string; value: any }) {
    if (field === 'cabor_id') {
        selectedCaborId.value = value;
        selectedKategoriId.value = '';
        fetchKategoriOptions(value);
    }
    if (field === 'cabor_kategori_id') {
        selectedKategoriId.value = value;
    }
    if (field === 'tenaga_pendukung_id') {
        selectedTenagaPendukungId.value = value;
    }
}

const formInitialData = computed(() => ({
    cabor_id: selectedCaborId.value,
    cabor_kategori_id: selectedKategoriId.value,
    tenaga_pendukung_id: selectedTenagaPendukungId.value,
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
        name: 'tenaga_pendukung_id',
        label: 'Tenaga Pendukung',
        type: 'select' as const,
        options: tenagaPendukungOptions.value,
        placeholder: 'Pilih Tenaga Pendukung',
        required: true,
        modelValue: selectedTenagaPendukungId.value,
        onUpdateModelValue: (val: any) => handleFieldUpdate({ field: 'tenaga_pendukung_id', value: val }),
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

const handleSave = (form: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const dataToSave: Record<string, any> = {
        ...form,
        cabor_id: selectedCaborId.value,
        cabor_kategori_id: selectedKategoriId.value,
        tenaga_pendukung_id: selectedTenagaPendukungId.value,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/pemeriksaan',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Pemeriksaan berhasil ditambahkan' : 'Pemeriksaan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan pemeriksaan' : 'Gagal memperbarui pemeriksaan',
        redirectUrl: '/pemeriksaan',
        onError: setFormErrors,
    });
};
</script>

<template>
    <FormInput
        :form-inputs="formInputs"
        :initial-data="formInitialData"
        @save="handleSave"
        @field-updated="handleFieldUpdate"
        :disable-auto-reset="props.mode === 'create'"
    />
</template>
