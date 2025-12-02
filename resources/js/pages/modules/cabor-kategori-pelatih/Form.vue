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
    jenis_pelatih: props.initialData?.jenis_pelatih || '',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'jenis_pelatih',
        label: 'Jenis Pelatih',
        type: 'text' as const,
        placeholder: 'Masukkan jenis pelatih',
        required: false,
    },
]);

const handleSave = (form: any) => {
    const formData: Record<string, any> = {
        jenis_pelatih: form.jenis_pelatih,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/cabor-kategori-pelatih',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Jenis pelatih berhasil ditambahkan!' : 'Jenis pelatih berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan jenis pelatih.' : 'Gagal memperbarui jenis pelatih.',
        redirectUrl: `/cabor-kategori/${props.initialData?.cabor_kategori_id}/pelatih`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
