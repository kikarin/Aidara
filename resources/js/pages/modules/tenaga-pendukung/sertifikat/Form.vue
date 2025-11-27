<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    tenagaPendukungId: number | null;
    initialData?: Record<string, any>;
}>();

const formData = ref({
    tenaga_pendukung_id: props.tenagaPendukungId || null,
    nama_sertifikat: props.initialData?.nama_sertifikat || '',
    penyelenggara: props.initialData?.penyelenggara || '',
    tanggal_terbit: props.initialData?.tanggal_terbit || '',
    file: null,
    id: props.initialData?.id || undefined,
    is_delete_file: 0,
});

const formInputs = computed(() => [
    { name: 'nama_sertifikat', label: 'Nama Sertifikat', type: 'text' as const, placeholder: 'Masukkan nama sertifikat', required: true },
    { name: 'penyelenggara', label: 'Penyelenggara', type: 'text' as const, placeholder: 'Masukkan nama penyelenggara' },
    { name: 'tanggal_terbit', label: 'Tanggal Terbit', type: 'date' as const, placeholder: 'Pilih tanggal terbit' },
    { name: 'file', label: 'File Sertifikat', type: 'file' as const, placeholder: 'Upload file sertifikat (PDF, JPG, PNG)' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    if (!props.tenagaPendukungId) {
        toast({ title: 'ID Tenaga Pendukung tidak ditemukan', variant: 'destructive' });
        return;
    }

    const formFields = { ...formData.value, ...dataFromFormInput };
    const url = `/tenaga-pendukung/${props.tenagaPendukungId}/sertifikat`;
    save(formFields, {
        url: url,
        mode: props.mode,
        id: formData.value.id,
        successMessage: props.mode === 'create' ? 'Sertifikat berhasil ditambahkan!' : 'Sertifikat berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan sertifikat.' : 'Gagal memperbarui sertifikat.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        onSuccess: () => {
            router.visit(`/tenaga-pendukung/${props.tenagaPendukungId}/sertifikat`);
        },
    });
};
</script>

<template>
    <div>
        <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
    </div>
</template>
