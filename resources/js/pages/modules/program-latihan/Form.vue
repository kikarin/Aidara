<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const caborOptions = ref<{ value: number; label: string }[]>([]);
const caborKategoriOptions = ref<{ value: number; label: string }[]>([]);
const selectedCaborId = ref(props.initialData?.cabor_id || '');
const selectedKategoriId = ref(props.initialData?.cabor_kategori_id || '');

const fetchCaborOptions = async () => {
    const res = await axios.get('/api/cabor-list');
    caborOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

const fetchCaborKategoriOptions = async (caborId: number | string) => {
    if (!caborId) {
        caborKategoriOptions.value = [];
        return;
    }
    const res = await axios.get(`/api/cabor-kategori-by-cabor/${caborId}`);
    caborKategoriOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

onMounted(async () => {
    await fetchCaborOptions();
    if (selectedCaborId.value) {
        await fetchCaborKategoriOptions(selectedCaborId.value);
    }
});

watch(selectedCaborId, async (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedKategoriId.value = '';
        await fetchCaborKategoriOptions(newVal);
    }
});

const formInitialData = computed(() => ({
    nama_program: props.initialData?.nama_program || '',
    periode_mulai: props.initialData?.periode_mulai || '',
    periode_selesai: props.initialData?.periode_selesai || '',
    tahap: props.initialData?.tahap || '',
    keterangan: props.initialData?.keterangan || '',
    cabor_id: selectedCaborId.value,
    cabor_kategori_id: selectedKategoriId.value,
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
        onUpdateModelValue: (val: any) => (selectedCaborId.value = val),
    },
    {
        name: 'cabor_kategori_id',
        label: 'Kategori',
        type: 'select' as const,
        options: caborKategoriOptions.value,
        placeholder: selectedCaborId.value ? 'Pilih Kategori' : 'Pilih cabor terlebih dahulu',
        required: true,
        disabled: !selectedCaborId.value,
        modelValue: selectedKategoriId.value,
        onUpdateModelValue: (val: any) => (selectedKategoriId.value = val),
    },
    {
        name: 'nama_program',
        label: 'Nama Program',
        type: 'text' as const,
        placeholder: 'Masukkan nama program',
        required: true,
    },
    {
        name: 'periode_mulai',
        label: 'Periode Mulai',
        type: 'date' as const,
        required: true,
    },
    {
        name: 'periode_selesai',
        label: 'Periode Selesai',
        type: 'date' as const,
        required: true,
    },
    {
        name: 'tahap',
        label: 'Tahap',
        type: 'select' as const,
        options: [
            { value: 'persiapan umum', label: 'Persiapan Umum' },
            { value: 'persiapan khusus', label: 'Persiapan Khusus' },
            { value: 'prapertandingan', label: 'Prapertandingan' },
            { value: 'pertandingan', label: 'Pertandingan' },
            { value: 'transisi', label: 'Transisi' },
        ],
        placeholder: 'Pilih tahap',
        required: false,
    },
    {
        name: 'keterangan',
        label: 'Keterangan',
        type: 'textarea' as const,
        placeholder: 'Masukkan keterangan (opsional)',
        required: false,
    },
]);

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        ...form,
        cabor_id: selectedCaborId.value,
        cabor_kategori_id: selectedKategoriId.value,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/program-latihan',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Program latihan berhasil ditambahkan' : 'Program latihan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan program latihan' : 'Gagal memperbarui program latihan',
        redirectUrl: '/program-latihan',
    });
};

// Tambahkan handler untuk event field-updated dari FormInput
function handleFieldUpdate({ field, value }: { field: string; value: any }) {
    if (field === 'cabor_id') {
        selectedCaborId.value = value;
        selectedKategoriId.value = '';
        fetchCaborKategoriOptions(value);
    }
    if (field === 'cabor_kategori_id') {
        selectedKategoriId.value = value;
    }
}
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
