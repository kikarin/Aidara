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
    nama: props.initialData?.nama || '',
    jenis_kelamin: props.initialData?.jenis_kelamin || '',
    tempat_lahir: props.initialData?.tempat_lahir || '',
    tanggal_lahir: props.initialData?.tanggal_lahir || '',
    tanggal_bergabung: props.initialData?.tanggal_bergabung || '',
    alamat: props.initialData?.alamat || '',
    kecamatan_id: props.initialData?.kecamatan_id || '',
    kelurahan_id: props.initialData?.kelurahan_id || '',
    no_hp: props.initialData?.no_hp || '',
    email: props.initialData?.email || user.value?.email || '', // Auto-fill email dari user yang login
    kategori_pesertas: Array.isArray(props.initialData?.kategori_pesertas) ? props.initialData.kategori_pesertas : [],
    is_active: props.initialData?.is_active !== undefined ? props.initialData.is_active : 1,
    foto: props.initialData?.foto || '',
    id: props.initialData?.id || undefined,
    file: null,
    is_delete_foto: 0,
    // Cabang Olahraga fields (multi-select)
    cabor_ids: Array.isArray(props.initialData?.cabor_ids) 
        ? props.initialData.cabor_ids 
        : props.initialData?.cabor_id 
            ? [props.initialData.cabor_id] 
            : [],
    jenis_tenaga_pendukung: props.initialData?.jenis_tenaga_pendukung || '',
});

const kecamatanOptions = ref<{ value: number; label: string }[]>([]);
const kelurahanOptions = ref<{ value: number; label: string }[]>([]);
const kategoriPesertaOptions = ref<{ value: number; label: string }[]>([]);
const caborOptions = ref<{ value: number; label: string }[]>([]);
const allCaborOptions = ref<{ value: number; label: string; kategori_peserta_id: number | null }[]>([]);

onMounted(async () => {
    try {
        // Load kecamatan
    try {
        const res = await axios.get('/api/kecamatan-list');
            const kecamatanData = Array.isArray(res.data) ? res.data : [];
            kecamatanOptions.value = kecamatanData.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
        } catch (error) {
            console.error('Gagal mengambil data kecamatan:', error);
            kecamatanOptions.value = [];
        }

        if (props.mode === 'edit' && formData.value.kecamatan_id) {
            try {
            const kelurahanRes = await axios.get(`/api/kelurahan-by-kecamatan/${formData.value.kecamatan_id}`);
                const kelurahanData = Array.isArray(kelurahanRes.data) ? kelurahanRes.data : [];
                kelurahanOptions.value = kelurahanData.map((item: { id: number; nama: string }) => ({ value: item.id, label: item.nama }));
            } catch (error) {
                console.error('Gagal mengambil data kelurahan:', error);
                kelurahanOptions.value = [];
            }
        }

        // Load kategori peserta
        try {
        const kategoriPesertaRes = await axios.get('/api/kategori-peserta-list');
            const kategoriData = Array.isArray(kategoriPesertaRes.data) ? kategoriPesertaRes.data : [];
            kategoriPesertaOptions.value = kategoriData.map((item: { id: number; nama: string }) => ({
            value: item.id,
            label: item.nama,
        }));
        } catch (error) {
            console.error('Gagal mengambil data kategori peserta:', error);
            kategoriPesertaOptions.value = [];
        }

        // Load cabor list (semua cabor untuk filtering nanti)
        try {
            // Gunakan /api/cabor-list dengan parameter for_peserta_form untuk mengecualikan filter
        const caborRes = await axios.get('/api/cabor-list', { params: { for_peserta_form: true } });
            const caborData = Array.isArray(caborRes.data) ? caborRes.data : [];
            
            allCaborOptions.value = caborData.map((item: any) => ({
            value: item.id,
            label: item.nama,
                kategori_peserta_id: item.kategori_peserta_id || null,
            }));
            
            // Filter cabor berdasarkan kategori peserta yang sudah dipilih (jika ada)
            // Pastikan allCaborOptions sudah terisi sebelum filter
            if (allCaborOptions.value.length > 0) {
                filterCaborByKategoriPeserta();
            }
        } catch (error) {
            console.error('Gagal mengambil data cabor:', error);
            allCaborOptions.value = [];
            caborOptions.value = [];
        }

        // Load kategori peserta yang sudah ada (untuk edit mode)
        // Cek dari initialData terlebih dahulu, lalu dari page.props
        if (props.mode === 'edit') {
            const existingKategori = props.initialData?.kategori_pesertas 
                || (page.props as any).kategori_pesertas 
                || (page.props as any).item?.kategori_pesertas;
            
            console.log('TenagaPendukung/Form.vue: Loading kategori peserta', {
                from_initialData: props.initialData?.kategori_pesertas,
                from_page_props: (page.props as any).kategori_pesertas,
                from_item: (page.props as any).item?.kategori_pesertas,
                existingKategori,
            });
            
            if (existingKategori && Array.isArray(existingKategori) && existingKategori.length > 0) {
                formData.value.kategori_pesertas = existingKategori;
                console.log('TenagaPendukung/Form.vue: Set kategori_pesertas to formData', formData.value.kategori_pesertas);
            } else if (existingKategori && Array.isArray(existingKategori)) {
                // Jika array kosong, tetap set untuk memastikan formData ter-update
                formData.value.kategori_pesertas = [];
            }

            // Load cabor data dari pivot jika ada
            // Gunakan cabor_ids dari item jika ada (sudah di-filter di backend)
            if (props.initialData?.cabor_ids && Array.isArray(props.initialData.cabor_ids) && props.initialData.cabor_ids.length > 0) {
                formData.value.cabor_ids = props.initialData.cabor_ids;
            } else if ((page.props as any).item?.cabor_ids && Array.isArray((page.props as any).item.cabor_ids) && (page.props as any).item.cabor_ids.length > 0) {
                formData.value.cabor_ids = (page.props as any).item.cabor_ids;
            } else {
                // Fallback: ambil dari cabor_kategori_tenaga_pendukung (hanya yang cabor_kategori_id null)
                const existingCaborKategoriTenagaPendukung = props.initialData?.cabor_kategori_tenaga_pendukung 
                    || (page.props as any).item?.cabor_kategori_tenaga_pendukung;
                
                if (existingCaborKategoriTenagaPendukung && Array.isArray(existingCaborKategoriTenagaPendukung) && existingCaborKategoriTenagaPendukung.length > 0) {
                    // Filter hanya yang cabor_kategori_id null (langsung ke cabor, tanpa kategori)
                    const directCabor = existingCaborKategoriTenagaPendukung.filter((item: any) => !item.cabor_kategori_id);
                    // Ambil semua cabor_id yang unik
                    const caborIds = [...new Set(directCabor.map((item: any) => item.cabor_id || item.cabor?.id).filter(Boolean))];
                    formData.value.cabor_ids = caborIds;
                } else if (props.initialData?.cabor_id || (page.props as any).item?.cabor_id) {
                    // Fallback untuk backward compatibility
                    const existingCabor = props.initialData?.cabor_id || (page.props as any).item?.cabor_id;
                    formData.value.cabor_ids = [existingCabor];
                }
            }
            
            const existingJenisTenagaPendukung = props.initialData?.jenis_tenaga_pendukung 
                || (page.props as any).item?.jenis_tenaga_pendukung;
            if (existingJenisTenagaPendukung) {
                formData.value.jenis_tenaga_pendukung = existingJenisTenagaPendukung;
            }
        }
    } catch (e) {
        console.error('Gagal mengambil data kecamatan/kelurahan/kategori peserta/cabor', e);
        kecamatanOptions.value = [];
        kategoriPesertaOptions.value = [];
        caborOptions.value = [];
    }
});

// Watch untuk update kategori_pesertas saat initialData berubah
watch(
    () => props.initialData?.kategori_pesertas,
    (newKategori) => {
        if (props.mode === 'edit' && Array.isArray(newKategori)) {
            formData.value.kategori_pesertas = newKategori;
        }
    },
    { immediate: true }
);

// Function untuk filter cabor berdasarkan kategori peserta
const filterCaborByKategoriPeserta = () => {
    // Pastikan allCaborOptions sudah terisi
    if (!allCaborOptions.value || allCaborOptions.value.length === 0) {
        caborOptions.value = [];
        return;
    }
    
    const kategoriPesertas = formData.value.kategori_pesertas;
    
    // Jika belum ada kategori peserta yang dipilih, tampilkan semua cabor
    if (!kategoriPesertas || !Array.isArray(kategoriPesertas) || kategoriPesertas.length === 0) {
        caborOptions.value = allCaborOptions.value.map((c: any) => ({
            value: c.value,
            label: c.label,
        }));
        return;
    }
    
    // Ambil kategori_peserta_id dari array kategori_pesertas
    const kategoriPesertaIds = kategoriPesertas.map((k: any) => typeof k === 'object' ? k.id : k);
    
    // Filter cabor yang memiliki kategori_peserta_id yang sama dengan yang dipilih
    // Jika cabor tidak punya kategori_peserta_id, tetap tampilkan (untuk backward compatibility)
    caborOptions.value = allCaborOptions.value.filter((cabor) => {
        // Jika cabor tidak punya kategori_peserta_id, tetap tampilkan
        if (!cabor.kategori_peserta_id) return true;
        // Jika punya kategori_peserta_id, filter berdasarkan yang dipilih
        return kategoriPesertaIds.includes(cabor.kategori_peserta_id);
    }).map((c: any) => ({
        value: c.value,
        label: c.label,
    }));
    
    // Reset cabor_ids jika cabor yang dipilih tidak ada di filtered list
    if (formData.value.cabor_ids && formData.value.cabor_ids.length > 0) {
        formData.value.cabor_ids = formData.value.cabor_ids.filter((caborId: number) => 
            caborOptions.value.find((c: any) => c.value === caborId)
        );
    }
};

// Watch untuk filter cabor saat kategori peserta berubah
watch(
    () => formData.value.kategori_pesertas,
    () => {
        filterCaborByKategoriPeserta();
    },
    { deep: true }
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
        label: 'NIK',
        type: 'text' as const,
        placeholder: 'Masukkan NIK (16 digit)',
        required: true,
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
    { name: 'alamat', label: 'Alamat', type: 'textarea' as const, placeholder: 'Masukkan alamat' },
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
    // Cabang Olahraga Section
    {
        name: 'cabor_ids',
        label: 'Cabang Olahraga',
        type: 'multi-select' as const,
        placeholder: 'Pilih Cabang Olahraga (Opsional)',
        required: false,
        options: caborOptions.value,
        help: 'Pilih satu atau lebih cabang olahraga untuk tenaga pendukung ini',
    },
    {
        name: 'jenis_tenaga_pendukung',
        label: 'Jenis Tenaga Pendukung',
        type: 'text' as const,
        placeholder: 'Contoh: Fisioterapis, Dokter Tim, Manajer',
        required: false,
        help: 'Masukkan jenis atau spesialisasi tenaga pendukung',
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

    // Pastikan cabor_ids selalu array, ambil dari dataFromFormInput atau formData
    const caborIds = Array.isArray(dataFromFormInput.cabor_ids) 
        ? dataFromFormInput.cabor_ids.filter((id: any) => id !== null && id !== undefined)
        : (Array.isArray(formData.value.cabor_ids) 
            ? formData.value.cabor_ids.filter((id: any) => id !== null && id !== undefined)
            : []);

    const formFields = {
        ...formData.value,
        ...dataFromFormInput,
        kategori_pesertas: kategoriPesertaIds,
        // Cabang Olahraga fields (multi-select) - selalu kirim sebagai array
        cabor_ids: caborIds,
        jenis_tenaga_pendukung: dataFromFormInput.jenis_tenaga_pendukung || formData.value.jenis_tenaga_pendukung || null,
    };

    // Debug log
    console.log('TenagaPendukung Form - handleSave:', {
        cabor_ids: formFields.cabor_ids,
        jenis_tenaga_pendukung: formFields.jenis_tenaga_pendukung,
        mode: props.mode,
    });

    // Jika user masih pending, jangan kirim is_active (biarkan tetap 0 sampai di-approve)
    if (isPendingRegistration.value) {
        delete formFields.is_active;
    }

    const url = '/tenaga-pendukung';

    save(formFields, {
        url: url,
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Tenaga Pendukung berhasil ditambahkan!' : 'Tenaga Pendukung berhasil diperbarui!',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan Tenaga Pendukung.' : 'Gagal memperbarui Tenaga Pendukung.',
        onError: (errors: Record<string, string>) => {
            setFormErrors(errors);
        },
        onSuccess: (page: any) => {
            const id = page?.props?.item?.id || page?.props?.item?.data?.id || props.initialData?.id;
            // Ambil tab aktif dari URL saat ini untuk mempertahankan tab setelah save
            const currentUrl = window.location.href;
            const urlParams = new URLSearchParams(currentUrl.split('?')[1] || '');
            const currentTab = urlParams.get('tab') || 'tenaga-pendukung-data';
            
            if (props.mode === 'create') {
                if (id) {
                    router.visit(`/tenaga-pendukung/${id}/edit?tab=${currentTab}`, { only: ['item', 'kategori_pesertas'] });
                } else {
                    router.visit('/tenaga-pendukung');
                }
            } else if (props.mode === 'edit') {
                if (id) {
                    // Tetap di halaman edit dengan tab yang sama, tidak redirect ke index
                    router.visit(`/tenaga-pendukung/${id}/edit?tab=${currentTab}`, { only: ['item', 'kategori_pesertas'] });
                } else {
                    router.visit('/tenaga-pendukung');
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
