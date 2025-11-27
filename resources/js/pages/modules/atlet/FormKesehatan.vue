<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();
const page = usePage();

interface FlashMessages {
    success?: string;
    error?: string;
    kesehatanId?: number;
}

const props = defineProps<{
    atletId: number | null; // ID atlet induk
    mode: 'create' | 'edit';
    initialData?: any;
}>();

const formData = ref<Record<string, any>>({
    id: props.initialData?.id || undefined,
    atlet_id: props.atletId,
    golongan_darah: props.initialData?.golongan_darah || '',
    tinggi_badan: props.initialData?.tinggi_badan || '',
    berat_badan: props.initialData?.berat_badan || '',
    penglihatan: props.initialData?.penglihatan || '',
    pendengaran: props.initialData?.pendengaran || '',
    riwayat_penyakit: props.initialData?.riwayat_penyakit || '',
    alergi: props.initialData?.alergi || '',
});

const formInputInitialData = computed(() => {
    console.log('Atlet/FormKesehatan.vue: formInputInitialData computed property is being evaluated', formData.value);
    return { ...formData.value };
});

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

onMounted(async () => {
    const flashedKesehatanId = (page.props.flash as FlashMessages)?.kesehatanId;
    if (flashedKesehatanId) {
        console.log('Flashed Kesehatan ID detected:', flashedKesehatanId);
        formData.value.id = flashedKesehatanId;
    }

    if (props.atletId) {
        try {
            const res = await axios.get(`/atlet/${props.atletId}/kesehatan`);
            if (res.data) {
                Object.assign(formData.value, res.data);
                console.log('Atlet/FormKesehatan.vue: Fetched existing kesehatan data and updated formData:', formData.value);
            } else {
                console.log('Atlet/FormKesehatan.vue: No existing kesehatan data found for atlet_id:', props.atletId);
            }
        } catch (e: any) {
            console.error('Gagal mengambil data atlet kesehatan', e);
            if (e.response && e.response.status !== 404) {
                toast({ title: 'Terjadi kesalahan saat memuat data kesehatan atlet', variant: 'destructive' });
            }
        }
    }
});

const formInputs = computed(() => [
    {
        name: 'golongan_darah',
        label: 'Golongan Darah',
        type: 'select' as const,
        placeholder: 'Pilih golongan darah',
        options: [
            { value: 'A', label: 'A' },
            { value: 'A+', label: 'A' },
            { value: 'B', label: 'B' },
            { value: 'B+', label: 'B' },
            { value: 'AB', label: 'AB' },
            { value: 'AB+', label: 'AB' },
            { value: 'O', label: 'O' },
            { value: 'O+', label: 'O' },
        ],
    },
    { name: 'tinggi_badan', label: 'Tinggi Badan (cm)', type: 'number' as const, placeholder: 'Masukkan tinggi badan', min: 0 },
    { name: 'berat_badan', label: 'Berat Badan (kg)', type: 'number' as const, placeholder: 'Masukkan berat badan', min: 0 },
    {
        name: 'penglihatan',
        label: 'Penglihatan',
        type: 'select' as const,
        placeholder: 'Pilih kondisi penglihatan',
        options: [
            { value: 'Normal', label: 'Normal' },
            { value: 'Minus', label: 'Minus' },
            { value: 'Plus', label: 'Plus' },
            { value: 'Silinder', label: 'Silinder' },
            { value: 'Buta Warna', label: 'Buta Warna' },
            { value: 'Rabun Jauh', label: 'Rabun Jauh' },
            { value: 'Rabun Dekat', label: 'Rabun Dekat' },
            { value: 'Astigmatisma', label: 'Astigmatisma' },
            { value: 'Presbiopi', label: 'Presbiopi' },
            { value: 'Lainnya', label: 'Lainnya' },
        ],
    },
    {
        name: 'pendengaran',
        label: 'Pendengaran',
        type: 'select' as const,
        placeholder: 'Pilih kondisi pendengaran',
        options: [
            { value: 'Normal', label: 'Normal' },
            { value: 'Gangguan Ringan', label: 'Gangguan Ringan' },
            { value: 'Gangguan Sedang', label: 'Gangguan Sedang' },
            { value: 'Gangguan Berat', label: 'Gangguan Berat' },
            { value: 'Tuli', label: 'Tuli' },
            { value: 'Lainnya', label: 'Lainnya' },
        ],
    },
    {
        name: 'riwayat_penyakit',
        label: 'Riwayat Penyakit',
        type: 'textarea' as const,
        placeholder: 'Kosongkan jika tidak mempunyai riwayat penyakit',
    },
    { name: 'alergi', label: 'Alergi', type: 'textarea' as const, placeholder: 'Kosongkan jika tidak mempunyai alergi' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };

    if (props.atletId && !formFields.atlet_id) {
        formFields.atlet_id = props.atletId;
    }

    const baseUrl = `/atlet/${props.atletId}/kesehatan`;

    console.log('Atlet/FormKesehatan.vue: Form fields to send (before save call):', formFields);
    console.log('Atlet/FormKesehatan.vue: Determined base URL:', baseUrl);
    console.log('Atlet/FormKesehatan.vue: Mode:', props.mode);
    console.log('Atlet/FormKesehatan.vue: Existing Kesehatan ID (if edit mode):', formData.value.id);

    save(formFields, {
        url: baseUrl,
        mode: formData.value.id ? 'edit' : 'create',
        id: formData.value.id,
        successMessage: formData.value.id ? 'Data kesehatan atlet berhasil diperbarui!' : 'Data kesehatan atlet berhasil ditambahkan!',
        errorMessage: formData.value.id ? 'Gagal memperbarui data kesehatan atlet.' : 'Gagal menyimpan data kesehatan atlet.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        redirectUrl: `/atlet/${props.atletId}/edit?tab=kesehatan-data`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInputInitialData" @save="handleSave" />
</template>
