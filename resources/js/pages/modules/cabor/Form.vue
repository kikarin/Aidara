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

const kategoriPesertaOptions = ref<{ value: number; label: string }[]>([]);

onMounted(async () => {
    try {
        const res = await axios.get('/api/kategori-peserta-list');
        kategoriPesertaOptions.value = (res.data || []).map((item: { id: number; nama: string }) => ({
            value: item.id,
            label: item.nama,
        }));
    } catch (error) {
        console.error('Gagal mengambil data kategori peserta:', error);
    }
});

const formData = computed(() => {
    const base = {
        nama: props.initialData?.nama || '',
        deskripsi: props.initialData?.deskripsi || '',
        kategori_peserta_id: props.initialData?.kategori_peserta_id || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = computed(() => [
    {
        name: 'nama',
        label: 'Nama Cabor',
        type: 'text' as const,
        placeholder: 'Masukkan nama cabor',
        required: true,
    },
    {
        name: 'kategori_peserta_id',
        label: 'Jenis (POPM/NVC/KONI)',
        type: 'select' as const,
        options: kategoriPesertaOptions.value,
        placeholder: 'Pilih Jenis',
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
        nama: form.nama,
        deskripsi: form.deskripsi,
        kategori_peserta_id: form.kategori_peserta_id || null,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/cabor',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Cabor berhasil ditambahkan' : 'Cabor berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan cabor' : 'Gagal memperbarui cabor',
        redirectUrl: '/cabor',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
