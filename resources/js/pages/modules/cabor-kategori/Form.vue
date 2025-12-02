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
const kategoriPesertaOptions = ref<{ value: number; label: string }[]>([]);

const fetchCaborOptions = async () => {
    const res = await axios.get('/api/cabor-list');
    caborOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

const fetchKategoriPesertaOptions = async () => {
    try {
        const res = await axios.get('/api/kategori-peserta-list');
        kategoriPesertaOptions.value = (res.data || []).map((item: any) => ({ 
            value: item.id, 
            label: item.nama 
        }));
    } catch (error) {
        console.error('Gagal mengambil data kategori peserta:', error);
        kategoriPesertaOptions.value = [];
    }
};

onMounted(() => {
    fetchCaborOptions();
    fetchKategoriPesertaOptions();
});

const formData = computed(() => {
    const base = {
        cabor_id: props.initialData?.cabor_id || '',
        nama: props.initialData?.nama || '',
        jenis_kelamin: props.initialData?.jenis_kelamin || '',
        kategori_peserta_id: props.initialData?.kategori_peserta_id || '',
        deskripsi: props.initialData?.deskripsi || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = computed(() => [
    {
        name: 'cabor_id',
        label: 'Cabor',
        type: 'select' as const,
        options: caborOptions.value,
        placeholder: 'Pilih Cabor',
        required: true,
    },
    {
        name: 'nama',
        label: 'Sub Kategori',
        type: 'dynamic-sub' as const,
        placeholder: 'Masukkan sub kategori (contoh: Sprint, Kelas, 200 meter)',
        required: true,
    },
    {
        name: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        type: 'select' as const,
        options: [
            { value: 'L', label: 'Laki-laki' },
            { value: 'P', label: 'Perempuan' },
            { value: 'C', label: 'Campuran' },
        ],
        placeholder: 'Pilih Jenis Kelamin',
        required: true,
    },
    {
        name: 'kategori_peserta_id',
        label: 'Jenis',
        type: 'select' as const,
        options: kategoriPesertaOptions.value,
        placeholder: 'Pilih Jenis (Opsional)',
        required: false,
    },
    {
        name: 'deskripsi',
        label: 'Deskripsi',
        type: 'textarea' as const,
        placeholder: 'Masukkan deskripsi (opsional)',
        required: false,
    },
]);

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        cabor_id: form.cabor_id,
        nama: form.nama,
        jenis_kelamin: form.jenis_kelamin,
        kategori_peserta_id: form.kategori_peserta_id || null,
        deskripsi: form.deskripsi,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/cabor-kategori',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Kategori berhasil ditambahkan' : 'Kategori berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan kategori' : 'Gagal memperbarui kategori',
        redirectUrl: '/cabor-kategori',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
