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
        deskripsi: props.initialData?.deskripsi || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = [
    {
        name: 'nama',
        label: 'Nama Cabor',
        type: 'text' as const,
        placeholder: 'Masukkan nama cabor',
        required: true,
    },
    {
        name: 'deskripsi',
        label: 'Deskripsi',
        type: 'textarea' as const,
        placeholder: 'Masukkan deskripsi (opsional)',
        required: false,
    },
];

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        nama: form.nama,
        deskripsi: form.deskripsi,
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
