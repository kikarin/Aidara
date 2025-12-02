<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    tenagaPendukungId: number | null;
    mode: 'create' | 'edit';
    initialData?: any;
    redirectUrl?: string;
}>();

const formData = ref<Record<string, any>>({
    id: props.initialData?.id || undefined,
    tenaga_pendukung_id: props.tenagaPendukungId,
    jenis_dokumen_id: props.initialData?.jenis_dokumen_id || null,
    nomor: props.initialData?.nomor || '',
    file: null,
});

const formInputInitialData = computed(() => ({ ...formData.value }));

const jenisDokumenOptions = ref<{ value: number; label: string }[]>([]);

watch(
    () => props.initialData,
    (newVal) => {
        if (newVal) {
            Object.assign(formData.value, newVal);
            if (props.tenagaPendukungId) {
                formData.value.tenaga_pendukung_id = props.tenagaPendukungId;
            }
        }
    },
    { immediate: true, deep: true },
);

onMounted(async () => {
    try {
        const res = await axios.get('/api/jenis-dokumen-list');
        jenisDokumenOptions.value = res.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
    } catch (e) {
        console.error('Gagal mengambil data jenis dokumen', e);
        toast({ title: 'Gagal memuat daftar jenis dokumen', variant: 'destructive' });
        jenisDokumenOptions.value = [];
    }
});

const formInputs = computed(() => [
    {
        name: 'jenis_dokumen_id',
        label: 'Jenis Dokumen',
        type: 'select' as const,
        placeholder: 'Pilih Jenis Dokumen',
        options: jenisDokumenOptions.value,
    },
    { name: 'nomor', label: 'Nomor Dokumen', type: 'text' as const, placeholder: 'Masukkan nomor dokumen' },
    { name: 'file', label: 'File Dokumen', type: 'file' as const, placeholder: 'Upload file dokumen (pdf/gambar)' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };
    if (props.tenagaPendukungId && !formFields.tenaga_pendukung_id) {
        formFields.tenaga_pendukung_id = props.tenagaPendukungId;
    }
    const baseUrl = `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen`;
    save(formFields, {
        url: baseUrl,
        mode: formData.value.id ? 'edit' : 'create',
        id: formData.value.id,
        successMessage: formData.value.id ? 'Dokumen berhasil diperbarui!' : 'Dokumen berhasil ditambahkan!',
        errorMessage: formData.value.id ? 'Gagal memperbarui dokumen.' : 'Gagal menambah dokumen.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        redirectUrl: props.redirectUrl ?? `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInputInitialData" @save="handleSave" />
</template>
