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
        label: 'Nama Jenis Tenaga Pendukung',
        type: 'text' as const,
        placeholder: 'Masukkan nama jenis tenaga pendukung',
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
        url: '/data-master/jenis-tenaga-pendukung',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage:
            props.mode === 'create' ? 'Data jenis tenaga pendukung berhasil ditambahkan' : 'Data jenis tenaga pendukung berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data jenis tenaga pendukung' : 'Gagal memperbarui data jenis tenaga pendukung',
        redirectUrl: '/data-master/jenis-tenaga-pendukung',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
