<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, onMounted } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = computed(() => {
    const base = {
        nama_event: props.initialData?.nama_event || '',
        deskripsi: props.initialData?.deskripsi || '',
        foto: null, // Jangan set dari initialData karena itu string path, bukan File object
        kategori_event_id: props.initialData?.kategori_event_id || '',
        tingkat_event_id: props.initialData?.tingkat_event_id || '',
        lokasi: props.initialData?.lokasi || '',
        tanggal_mulai: props.initialData?.tanggal_mulai || '',
        tanggal_selesai: props.initialData?.tanggal_selesai || '',
        status: props.initialData?.status || 'draft',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = [
    {
        name: 'nama_event',
        label: 'Nama Event',
        type: 'text' as const,
        placeholder: 'Masukkan nama event',
        required: true,
    },
    {
        name: 'deskripsi',
        label: 'Deskripsi',
        type: 'textarea' as const,
        placeholder: 'Masukkan deskripsi event',
        required: false,
    },
    {
        name: 'foto',
        label: 'Foto Event',
        type: 'file' as const,
        placeholder: 'Upload foto event',
        required: false,
        help: 'Format: JPG, PNG. Maksimal 5MB.',
    },
    {
        name: 'kategori_event_id',
        label: 'Kategori Event',
        type: 'select' as const,
        placeholder: 'Pilih kategori event',
        required: false,
        options: [],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        name: 'tingkat_event_id',
        label: 'Tingkat Event',
        type: 'select' as const,
        placeholder: 'Pilih tingkat event',
        required: false,
        options: [],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        name: 'lokasi',
        label: 'Lokasi',
        type: 'text' as const,
        placeholder: 'Masukkan lokasi event',
        required: false,
    },
    {
        name: 'tanggal_mulai',
        label: 'Tanggal Mulai',
        type: 'date' as const,
        placeholder: 'Pilih tanggal mulai',
        required: true,
    },
    {
        name: 'tanggal_selesai',
        label: 'Tanggal Selesai',
        type: 'date' as const,
        placeholder: 'Pilih tanggal selesai',
        required: true,
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select' as const,
        placeholder: 'Pilih status',
        required: true,
        options: [
            { label: 'Draft', value: 'draft' },
            { label: 'Publish', value: 'publish' },
            { label: 'Selesai', value: 'selesai' },
            { label: 'Dibatalkan', value: 'dibatalkan' },
        ],
        optionLabel: 'label',
        optionValue: 'value',
    },
];

const loadSelectOptions = async () => {
    try {
        const caborKategoriResponse = await axios.get('/api/cabor-kategori');
        const caborKategoriOptions = caborKategoriResponse.data.data.map((item: any) => ({
            label: `${item.cabor_nama} - ${item.nama}`,
            value: item.id,
        }));
        formInputs.find((input) => input.name === 'kategori_event_id')!.options = caborKategoriOptions;

        const tingkatResponse = await axios.get('/api/tingkat');
        const tingkatOptions = tingkatResponse.data.data.map((item: any) => ({
            label: item.nama,
            value: item.id,
        }));
        formInputs.find((input) => input.name === 'tingkat_event_id')!.options = tingkatOptions;
    } catch (error) {
        console.error('Error loading select options:', error);
    }
};

onMounted(() => {
    loadSelectOptions();
});

const handleSave = (form: any, setFormErrors?: (errors: Record<string, string>) => void) => {
    const dataToSave: Record<string, any> = {
        nama_event: form.nama_event,
        deskripsi: form.deskripsi || null,
        kategori_event_id: form.kategori_event_id || null,
        tingkat_event_id: form.tingkat_event_id || null,
        lokasi: form.lokasi || null,
        tanggal_mulai: form.tanggal_mulai,
        tanggal_selesai: form.tanggal_selesai,
        status: form.status,
    };

    // Tambahkan foto jika ada (bisa berupa File object)
    if (form.foto instanceof File) {
        dataToSave.foto = form.foto;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }

    save(dataToSave, {
        url: '/data-master/event',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Data event berhasil ditambahkan' : 'Data event berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data event' : 'Gagal memperbarui data event',
        redirectUrl: '/data-master/event',
        setFormErrors,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>

