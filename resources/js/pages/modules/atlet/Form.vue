<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const page = usePage();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

// Ambil user dari page props untuk auto-fill email
const user = computed(() => (page.props as any)?.auth?.user);
const isPendingRegistration = computed(() => user.value?.registration_status === 'pending');

const formData = ref({
    nik: props.initialData?.nik || '',
    nisn: props.initialData?.nisn || '',
    nama: props.initialData?.nama || '',
    jenis_kelamin: props.initialData?.jenis_kelamin || '',
    tempat_lahir: props.initialData?.tempat_lahir || '',
    tanggal_lahir: props.initialData?.tanggal_lahir || '',
    agama: props.initialData?.agama || '',
    tanggal_bergabung: props.initialData?.tanggal_bergabung || '',
    alamat: props.initialData?.alamat || '',
    sekolah: props.initialData?.sekolah || '',
    kelas_sekolah: props.initialData?.kelas_sekolah || '',
    ukuran_baju: props.initialData?.ukuran_baju || '',
    ukuran_celana: props.initialData?.ukuran_celana || '',
    ukuran_sepatu: props.initialData?.ukuran_sepatu || '',
    kecamatan_id: props.initialData?.kecamatan_id || '',
    kelurahan_id: props.initialData?.kelurahan_id || '',
    kategori_atlet_id: props.initialData?.kategori_atlet_id || '',
    no_hp: props.initialData?.no_hp || '',
    email: props.initialData?.email || user.value?.email || '', // Auto-fill email dari user yang login
    kategori_pesertas: Array.isArray(props.initialData?.kategori_pesertas) 
        ? props.initialData.kategori_pesertas 
        : Array.isArray(props.initialData?.kategori_atlets) 
            ? props.initialData.kategori_atlets 
            : [],
    is_active: props.initialData?.is_active !== undefined ? props.initialData.is_active : 1,
    foto: props.initialData?.foto || '',
    id: props.initialData?.id || undefined,
    file: null,
    is_delete_foto: 0,
});

const kecamatanOptions = ref<{ value: number; label: string }[]>([]);
const kelurahanOptions = ref<{ value: number; label: string }[]>([]);
const kategoriPesertaOptions = ref<{ value: number; label: string }[]>([]);

onMounted(async () => {
    try {
        const res = await axios.get('/api/kecamatan-list');
        kecamatanOptions.value = (res.data || []).map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));

        if (props.mode === 'edit' && formData.value.kecamatan_id) {
            const kelurahanRes = await axios.get(`/api/kelurahan-by-kecamatan/${formData.value.kecamatan_id}`);
            kelurahanOptions.value = kelurahanRes.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
        }

        const kategoriPesertaRes = await axios.get('/api/kategori-peserta-list');
        kategoriPesertaOptions.value = (kategoriPesertaRes.data || []).map((item: { id: number; nama: string }) => ({
            value: item.id,
            label: item.nama,
        }));

        // Load kategori peserta yang sudah ada (untuk edit mode)
        // Cek dari initialData terlebih dahulu, lalu dari page.props
        if (props.mode === 'edit') {
            const existingKategori = props.initialData?.kategori_pesertas 
                || props.initialData?.kategori_atlets
                || (page.props as any).kategori_pesertas 
                || (page.props as any).kategori_atlets
                || (page.props as any).item?.kategori_pesertas
                || (page.props as any).item?.kategori_atlets;
            
            console.log('Atlet/Form.vue: Loading kategori peserta', {
                from_initialData_kategori_pesertas: props.initialData?.kategori_pesertas,
                from_initialData_kategori_atlets: props.initialData?.kategori_atlets,
                from_page_props_kategori_pesertas: (page.props as any).kategori_pesertas,
                from_page_props_kategori_atlets: (page.props as any).kategori_atlets,
                from_item_kategori_pesertas: (page.props as any).item?.kategori_pesertas,
                from_item_kategori_atlets: (page.props as any).item?.kategori_atlets,
                existingKategori,
            });
            
            if (existingKategori && Array.isArray(existingKategori) && existingKategori.length > 0) {
                formData.value.kategori_pesertas = existingKategori;
                console.log('Atlet/Form.vue: Set kategori_pesertas to formData', formData.value.kategori_pesertas);
            } else if (existingKategori && Array.isArray(existingKategori)) {
                // Jika array kosong, tetap set untuk memastikan formData ter-update
                formData.value.kategori_pesertas = [];
            }
        }
    } catch (e) {
        console.error('Gagal mengambil data kecamatan/kelurahan/kategori peserta', e);
        kecamatanOptions.value = [];
        kategoriPesertaOptions.value = [];
    }
});

// Watch untuk update kategori_pesertas saat initialData berubah
watch(
    () => props.initialData?.kategori_pesertas || props.initialData?.kategori_atlets,
    (newKategori) => {
        if (props.mode === 'edit' && Array.isArray(newKategori)) {
            formData.value.kategori_pesertas = newKategori;
            console.log('Atlet/Form.vue: Updated kategori_pesertas from watch', newKategori);
        }
    },
    { immediate: true }
);

watch(
    () => formData.value.kecamatan_id,
    async (newVal, oldVal) => {
        if (newVal !== oldVal) {
            kelurahanOptions.value = [];
            formData.value.kelurahan_id = '';
            if (newVal) {
                try {
                    const res = await axios.get(`/api/kelurahan-by-kecamatan/${newVal}`);
                    kelurahanOptions.value = res.data.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
                } catch (e) {
                    console.error('Gagal mengambil data kelurahan', e);
                    kelurahanOptions.value = [];
                }
            }
        }
    },
);

const formInputs = computed(() => [
    {
        name: 'nik',
        label: 'NIK/NISN',
        type: 'text' as const,
        placeholder: 'Masukkan NIK',
        required: false,
    },
    {
        name: 'nisn',
        label: 'NISN',
        type: 'text' as const,
        placeholder: 'Masukkan NISN',
        required: false,
    },
    { name: 'nama', label: 'Nama', type: 'text' as const, placeholder: 'Masukkan nama', required: true },
    {
        name: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        type: 'select' as const,
        required: true,
        options: [
            { value: 'L', label: 'Laki-laki' },
            { value: 'P', label: 'Perempuan' },
        ],
    },
    { name: 'tempat_lahir', label: 'Tempat Lahir', type: 'text' as const, placeholder: 'Masukkan tempat lahir' },
    { name: 'tanggal_lahir', label: 'Tanggal Lahir', type: 'date' as const, placeholder: 'Pilih tanggal lahir' },
    { name: 'agama', label: 'Agama', type: 'text' as const, placeholder: 'Masukkan agama' },
    { name: 'alamat', label: 'Alamat', type: 'textarea' as const, placeholder: 'Masukkan alamat' },
    { name: 'sekolah', label: 'Sekolah', type: 'text' as const, placeholder: 'Masukkan sekolah' },
    { name: 'kelas_sekolah', label: 'Kelas Sekolah', type: 'text' as const, placeholder: 'Masukkan kelas sekolah' },
    { name: 'ukuran_baju', label: 'Ukuran Baju', type: 'text' as const, placeholder: 'Masukkan ukuran baju' },
    { name: 'ukuran_celana', label: 'Ukuran Celana', type: 'text' as const, placeholder: 'Masukkan ukuran celana' },
    { name: 'ukuran_sepatu', label: 'Ukuran Sepatu', type: 'text' as const, placeholder: 'Masukkan ukuran sepatu' },
    { name: 'kecamatan_id', label: 'Kecamatan', type: 'select' as const, placeholder: 'Pilih Kecamatan', options: kecamatanOptions.value },
    { name: 'kelurahan_id', label: 'Kelurahan', type: 'select' as const, placeholder: 'Pilih Kelurahan', options: kelurahanOptions.value },
    { name: 'no_hp', label: 'No HP', type: 'text' as const, placeholder: 'Masukkan nomor HP' },
    { name: 'email', label: 'Email', type: 'email' as const, placeholder: 'Masukkan email' },
    { name: 'tanggal_bergabung', label: 'Tanggal Bergabung', type: 'date' as const, placeholder: 'Pilih tanggal bergabung' },
    {
        name: 'kategori_pesertas',
        label: 'Kategori Peserta',
        type: 'multi-select' as const,
        placeholder: 'Pilih Kategori Peserta (bisa lebih dari 1)',
        required: true,
        options: kategoriPesertaOptions.value,
        help: 'Pilih satu atau lebih kategori peserta',
    },
    {
        name: 'is_active',
        label: 'Status',
        type: 'select' as const,
        required: true,
        options: [
            { value: 1, label: 'Aktif' },
            { value: 0, label: 'Nonaktif' },
        ],
    },
    { name: 'file', label: 'Foto', type: 'file' as const, placeholder: 'Upload foto' },
]);

// Filter formInputs untuk hide is_active jika user masih pending
const filteredFormInputs = computed(() => {
    if (isPendingRegistration.value) {
        return formInputs.value.filter(input => input.name !== 'is_active');
    }
    return formInputs.value;
});

function handleFieldUpdate({ field, value }: { field: string; value: any }) {
    if (field === 'kecamatan_id') {
        formData.value.kecamatan_id = value;
    }
}

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    // Ambil kategori_pesertas dari dataFromFormInput (sudah dalam bentuk array)
    const kategoriPesertaIds = Array.isArray(dataFromFormInput.kategori_pesertas) 
        ? dataFromFormInput.kategori_pesertas.filter((id: any) => id !== null && id !== undefined)
        : [];

    const formFields = {
        ...formData.value,
        ...dataFromFormInput,
        kategori_pesertas: kategoriPesertaIds,
        // Backward compatibility
        kategori_atlets: kategoriPesertaIds,
    };

    // Jika user masih pending, jangan kirim is_active (biarkan tetap 0 sampai di-approve)
    if (isPendingRegistration.value) {
        delete formFields.is_active;
    }

    const url = '/atlet';

    console.log('Atlet/Form.vue: Form fields to send:', formFields);
    console.log('Atlet/Form.vue: Determined base URL:', url);

    save(formFields, {
        url: url,
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Atlet berhasil ditambahkan!' : 'Atlet berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan atlet.' : 'Gagal memperbarui atlet.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        onSuccess: (page: any) => {
            const id = page?.props?.item?.id || page?.props?.item?.data?.id || props.initialData?.id;
            // Ambil tab aktif dari URL saat ini untuk mempertahankan tab setelah save
            const currentUrl = window.location.href;
            const urlParams = new URLSearchParams(currentUrl.split('?')[1] || '');
            const currentTab = urlParams.get('tab') || 'atlet-data';
            
            if (props.mode === 'create') {
                if (id) {
                    router.visit(`/atlet/${id}/edit?tab=${currentTab}`, { only: ['item', 'kategori_pesertas', 'kategori_atlets'] });
                } else {
                    router.visit('/atlet');
                }
            } else if (props.mode === 'edit') {
                if (id) {
                    // Tetap di halaman edit dengan tab yang sama, tidak redirect ke index
                    router.visit(`/atlet/${id}/edit?tab=${currentTab}`, { only: ['item', 'kategori_pesertas', 'kategori_atlets'] });
                } else {
                    router.visit('/atlet');
                }
            }
        },
    });
};
</script>

<template>
    <FormInput
        :form-inputs="filteredFormInputs"
        :initial-data="formData"
        :disable-auto-reset="props.mode === 'create'"
        :saveText="props.mode === 'edit' ? 'Simpan Perubahan' : 'Simpan'"
        @save="handleSave"
        @field-updated="handleFieldUpdate"
    />
</template>
