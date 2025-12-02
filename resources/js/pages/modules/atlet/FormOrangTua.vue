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
    orangTuaId?: number;
}

const props = defineProps<{
    atletId: number | null; // ID atlet induk
    mode: 'create' | 'edit';
    initialData?: any;
}>();

const formData = ref<Record<string, any>>({
    id: props.initialData?.id || undefined,
    atlet_id: props.atletId,
    nama_ibu_kandung: props.initialData?.nama_ibu_kandung || '',
    tempat_lahir_ibu: props.initialData?.tempat_lahir_ibu || '',
    tanggal_lahir_ibu: props.initialData?.tanggal_lahir_ibu || '',
    alamat_ibu: props.initialData?.alamat_ibu || '',
    no_hp_ibu: props.initialData?.no_hp_ibu || '',
    pekerjaan_ibu: props.initialData?.pekerjaan_ibu || '',

    nama_ayah_kandung: props.initialData?.nama_ayah_kandung || '',
    tempat_lahir_ayah: props.initialData?.tempat_lahir_ayah || '',
    tanggal_lahir_ayah: props.initialData?.tanggal_lahir_ayah || '',
    alamat_ayah: props.initialData?.alamat_ayah || '',
    no_hp_ayah: props.initialData?.no_hp_ayah || '',
    pekerjaan_ayah: props.initialData?.pekerjaan_ayah || '',

    nama_wali: props.initialData?.nama_wali || '',
    tempat_lahir_wali: props.initialData?.tempat_wali || '',
    tanggal_lahir_wali: props.initialData?.tanggal_lahir_wali || '',
    alamat_wali: props.initialData?.alamat_wali || '',
    no_hp_wali: props.initialData?.no_hp_wali || '',
    pekerjaan_wali: props.initialData?.pekerjaan_wali || '',
});

const formInputInitialData = computed(() => {
    console.log('FormOrangTua.vue: formInputInitialData computed property is being evaluated', formData.value);
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
    const flashedOrangTuaId = (page.props.flash as FlashMessages)?.orangTuaId;
    if (flashedOrangTuaId) {
        console.log('Flashed Orang Tua ID detected:', flashedOrangTuaId);
        formData.value.id = flashedOrangTuaId;
    }

    if (props.atletId) {
        try {
            const res = await axios.get(`/atlet/${props.atletId}/orang-tua`);
            if (res.data) {
                Object.assign(formData.value, res.data);
                console.log('FormOrangTua.vue: Fetched existing orang tua data and updated formData:', formData.value);
            } else {
                console.log('FormOrangTua.vue: No existing orang tua data found for atlet_id:', props.atletId);
            }
        } catch (e: any) {
            console.error('Gagal mengambil data atlet orang tua', e);
            if (e.response && e.response.status !== 404) {
                toast({ title: 'Terjadi kesalahan saat memuat data orang tua/wali', variant: 'destructive' });
            }
        }
    }
});

const formInputs = computed(() => [
    { name: 'nama_ibu_kandung', label: 'Nama Ibu Kandung', type: 'text' as const, placeholder: 'Masukkan nama ibu kandung' },
    { name: 'tempat_lahir_ibu', label: 'Tempat Lahir Ibu', type: 'text' as const, placeholder: 'Masukkan tempat lahir ibu' },
    { name: 'tanggal_lahir_ibu', label: 'Tanggal Lahir Ibu', type: 'date' as const, placeholder: 'Pilih tanggal lahir ibu' },
    { name: 'alamat_ibu', label: 'Alamat Ibu', type: 'textarea' as const, placeholder: 'Masukkan alamat ibu' },
    { name: 'no_hp_ibu', label: 'No. HP Ibu', type: 'text' as const, placeholder: 'Masukkan nomor HP ibu' },
    { name: 'pekerjaan_ibu', label: 'Pekerjaan Ibu', type: 'text' as const, placeholder: 'Masukkan pekerjaan ibu' },

    { name: 'nama_ayah_kandung', label: 'Nama Ayah Kandung', type: 'text' as const, placeholder: 'Masukkan nama ayah kandung' },
    { name: 'tempat_lahir_ayah', label: 'Tempat Lahir Ayah', type: 'text' as const, placeholder: 'Masukkan tempat lahir ayah' },
    { name: 'tanggal_lahir_ayah', label: 'Tanggal Lahir Ayah', type: 'date' as const, placeholder: 'Pilih tanggal lahir ayah' },
    { name: 'alamat_ayah', label: 'Alamat Ayah', type: 'textarea' as const, placeholder: 'Masukkan alamat ayah' },
    { name: 'no_hp_ayah', label: 'No. HP Ayah', type: 'text' as const, placeholder: 'Masukkan nomor HP ayah' },
    { name: 'pekerjaan_ayah', label: 'Pekerjaan Ayah', type: 'text' as const, placeholder: 'Masukkan pekerjaan ayah' },

    { name: 'nama_wali', label: 'Nama Wali', type: 'text' as const, placeholder: 'Masukkan nama wali' },
    { name: 'tempat_lahir_wali', label: 'Tempat Lahir Wali', type: 'text' as const, placeholder: 'Masukkan tempat lahir wali' },
    { name: 'tanggal_lahir_wali', label: 'Tanggal Lahir Wali', type: 'date' as const, placeholder: 'Pilih tanggal lahir wali' },
    { name: 'alamat_wali', label: 'Alamat Wali', type: 'textarea' as const, placeholder: 'Masukkan alamat wali' },
    { name: 'no_hp_wali', label: 'No. HP Wali', type: 'text' as const, placeholder: 'Masukkan nomor HP wali' },
    { name: 'pekerjaan_wali', label: 'Pekerjaan Wali', type: 'text' as const, placeholder: 'Masukkan pekerjaan wali' },
]);

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };

    if (props.atletId && !formFields.atlet_id) {
        formFields.atlet_id = props.atletId;
    }

    const baseUrl = `/atlet/${props.atletId}/orang-tua`;

    console.log('FormOrangTua.vue: Form fields to send (before save call):', formFields);
    console.log('FormOrangTua.vue: Determined base URL:', baseUrl);
    console.log('FormOrangTua.vue: Mode:', props.mode);
    console.log('FormOrangTua.vue: Existing Orang Tua ID (if edit mode):', formData.value.id);

    save(formFields, {
        url: baseUrl,
        mode: formData.value.id ? 'edit' : 'create',
        id: formData.value.id,
        successMessage: formData.value.id ? 'Data orang tua/wali berhasil diperbarui!' : 'Data orang tua/wali berhasil ditambahkan!',
        errorMessage: formData.value.id ? 'Gagal memperbarui data orang tua/wali.' : 'Gagal menyimpan data orang tua/wali.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        redirectUrl: `/atlet/${props.atletId}/edit?tab=orang-tua-data`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInputInitialData" @save="handleSave" />
</template>
