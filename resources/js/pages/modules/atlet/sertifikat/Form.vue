<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    atletId: number | null;
    mode: 'create' | 'edit';
    initialData?: any;
    redirectUrl?: string;
}>();

const formData = ref<Record<string, any>>({
    id: props.initialData?.id || undefined,
    atlet_id: props.atletId,
    nama_sertifikat: props.initialData?.nama_sertifikat || '',
    penyelenggara: props.initialData?.penyelenggara || '',
    tanggal_terbit: props.initialData?.tanggal_terbit || '',
    file: null,
});

const formInputInitialData = computed(() => ({ ...formData.value }));

watch(
    () => props.initialData,
    (newVal) => {
        if (newVal) {
            Object.assign(formData.value, newVal);
            if (props.atletId) {
                formData.value.atlet_id = props.atletId;
            }
        }
    },
    { immediate: true, deep: true },
);

const formInputs = computed(() => [
    { name: 'nama_sertifikat', label: 'Nama Sertifikat', type: 'text' as const, placeholder: 'Masukkan nama sertifikat', required: true },
    { name: 'penyelenggara', label: 'Penyelenggara', type: 'text' as const, placeholder: 'Masukkan nama penyelenggara' },
    { name: 'tanggal_terbit', label: 'Tanggal Terbit', type: 'date' as const, placeholder: 'Pilih tanggal terbit' },
    { name: 'file', label: 'File Sertifikat', type: 'file' as const, placeholder: 'Upload file sertifikat (pdf/gambar)' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };
    if (props.atletId && !formFields.atlet_id) {
        formFields.atlet_id = props.atletId;
    }
    const baseUrl = `/atlet/${props.atletId}/sertifikat`;
    save(formFields, {
        url: baseUrl,
        mode: formData.value.id ? 'edit' : 'create',
        id: formData.value.id,
        successMessage: formData.value.id ? 'Sertifikat berhasil diperbarui!' : 'Sertifikat berhasil ditambahkan!',
        errorMessage: formData.value.id ? 'Gagal memperbarui sertifikat.' : 'Gagal menambah sertifikat.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        redirectUrl: props.redirectUrl ?? `/atlet/${props.atletId}/sertifikat`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInputInitialData" @save="handleSave" />
</template>
