<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    pemeriksaan: any;
    peserta: any;
    parameters: any[];
    jenis_peserta?: string; // Added this line to match the new_code
}>();

// Ganti akses props.jenisPeserta menjadi props.jenis_peserta (snake_case) agar sesuai dengan props yang dikirim dari parent
const jenisPeserta = computed(
    () => props.jenis_peserta || (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('jenis_peserta') : 'atlet'),
);

const selectedParameterId = ref(props.initialData?.pemeriksaan_parameter_id || '');

const formInitialData = computed(() => ({
    pemeriksaan_parameter_id: selectedParameterId.value,
    nilai: props.initialData?.nilai ?? '',
    trend: props.initialData?.trend || 'stabil',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'pemeriksaan_parameter_id',
        label: 'Parameter',
        type: 'select' as const,
        options: (props.parameters || []).map((item: any) => ({
            value: item.id,
            label: item.mst_parameter ? `${item.mst_parameter.nama} (${item.mst_parameter.satuan})` : item.nama_parameter,
        })),
        placeholder: 'Pilih Parameter',
        required: true,
        modelValue: selectedParameterId.value,
        onUpdateModelValue: (val: any) => (selectedParameterId.value = val),
    },
    {
        name: 'nilai',
        label: 'Nilai',
        type: 'text' as const,
        required: true,
        placeholder: 'Contoh: 36.8 (suhu), 72 (nadi), 120/80 (tensi)',
    },
    {
        name: 'trend',
        label: 'Trend',
        type: 'select' as const,
        required: true,
        options: [
            { value: 'stabil', label: 'Stabil' },
            { value: 'penurunan', label: 'Penurunan' },
            { value: 'kenaikan', label: 'Kenaikan' },
        ],
    },
]);

const handleSave = (form: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const pemeriksaanId = props.pemeriksaan.id;
    const pesertaId = props.peserta.id;
    const id = props.initialData?.id;
    const dataToSave: Record<string, any> = {
        ...form,
        pemeriksaan_id: pemeriksaanId,
        pemeriksaan_peserta_id: pesertaId,
    };
    save(dataToSave, {
        url: `/pemeriksaan/${pemeriksaanId}/peserta/${pesertaId}/parameter`,
        mode: props.mode,
        id,
        successMessage: props.mode === 'create' ? 'Parameter peserta berhasil ditambahkan' : 'Parameter peserta berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan parameter peserta' : 'Gagal memperbarui parameter peserta',
        redirectUrl: `/pemeriksaan/${pemeriksaanId}/peserta/${pesertaId}/parameter?jenis_peserta=${jenisPeserta.value}`,
        onError: setFormErrors,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInitialData" @save="handleSave" />
</template>
