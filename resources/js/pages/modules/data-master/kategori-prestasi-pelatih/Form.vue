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
        label: 'Nama Kategori Prestasi Pelatih',
        type: 'text' as const,
        placeholder: 'Masukkan nama kategori prestasi pelatih',
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
        url: '/data-master/kategori-prestasi-pelatih',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage:
            props.mode === 'create' ? 'Data kategori prestasi pelatih berhasil ditambahkan' : 'Data kategori prestasi pelatih berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data kategori prestasi pelatih' : 'Gagal memperbarui data kategori prestasi pelatih',
        redirectUrl: '/data-master/kategori-prestasi-pelatih',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
