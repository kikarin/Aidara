<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = computed(() => {
    const base = {
        nama: props.initialData?.nama || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = [
    {
        name: 'nama',
        label: 'Nama Kategori Peserta',
        type: 'text' as const,
        placeholder: 'Masukkan nama kategori peserta',
        required: true,
    },
];

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        nama: form.nama,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }

    save(dataToSave, {
        url: '/data-master/kategori-peserta',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Data kategori peserta berhasil ditambahkan' : 'Data kategori peserta berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data kategori peserta' : 'Gagal memperbarui data kategori peserta',
        redirectUrl: '/data-master/kategori-peserta',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
