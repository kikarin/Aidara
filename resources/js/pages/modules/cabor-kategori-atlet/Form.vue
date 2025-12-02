<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = computed(() => ({
    posisi_atlet: props.initialData?.posisi_atlet || '',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'posisi_atlet',
        label: 'Posisi Atlet',
        type: 'text' as const,
        placeholder: 'Masukkan posisi atlet',
        required: false,
    },
]);

const handleSave = (form: any) => {
    const formData: Record<string, any> = {
        posisi_atlet: form.posisi_atlet,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/cabor-kategori-atlet',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Posisi atlet berhasil ditambahkan!' : 'Posisi atlet berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan posisi atlet.' : 'Gagal memperbarui posisi atlet.',
        redirectUrl: `/cabor-kategori/${props.initialData?.cabor_kategori_id}/atlet`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
