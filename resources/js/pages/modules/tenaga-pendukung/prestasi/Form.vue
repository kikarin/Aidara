<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    tenagaPendukungId: number | null;
    initialData?: Record<string, any>;
}>();

const formData = ref({
    tenaga_pendukung_id: props.tenagaPendukungId || null,
    nama_event: props.initialData?.nama_event || '',
    tingkat_id: props.initialData?.tingkat_id || null,
    tanggal: props.initialData?.tanggal || '',
    peringkat: props.initialData?.peringkat || '',
    keterangan: props.initialData?.keterangan || '',
    id: props.initialData?.id || undefined,
});

const tingkatOptions = ref<{ value: number; label: string }[]>([]);

onMounted(async () => {
    try {
        const res = await axios.get('/api/tingkat-list');
        tingkatOptions.value = res.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
    } catch (e) {
        console.error('Gagal mengambil data tingkat', e);
        toast({ title: 'Gagal memuat daftar tingkat', variant: 'destructive' });
        tingkatOptions.value = [];
    }
});

const formInputs = computed(() => [
    { name: 'nama_event', label: 'Nama Event', type: 'text' as const, placeholder: 'Masukkan nama event', required: true },
    { name: 'tingkat_id', label: 'Tingkat', type: 'select' as const, placeholder: 'Pilih Tingkat', options: tingkatOptions.value },
    { name: 'tanggal', label: 'Tanggal', type: 'date' as const, placeholder: 'Pilih tanggal', required: false },
    { name: 'peringkat', label: 'Peringkat', type: 'text' as const, placeholder: 'Masukkan peringkat' },
    { name: 'keterangan', label: 'Keterangan', type: 'textarea' as const, placeholder: 'Masukkan keterangan' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    if (!props.tenagaPendukungId) {
        toast({ title: 'ID Tenaga Pendukung tidak ditemukan', variant: 'destructive' });
        return;
    }

    const formFields = { ...formData.value, ...dataFromFormInput };

    const url = `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`;

    save(formFields, {
        url: url,
        mode: props.mode,
        id: formData.value.id,
        successMessage: props.mode === 'create' ? 'Prestasi berhasil ditambahkan!' : 'Prestasi berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan prestasi.' : 'Gagal memperbarui prestasi.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        onSuccess: () => {
            router.visit(`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`);
        },
    });
};
</script>

<template>
    <div>
        <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
    </div>
</template>
