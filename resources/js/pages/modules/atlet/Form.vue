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
    disabilitas: props.initialData?.disabilitas || '',
    klasifikasi: props.initialData?.klasifikasi || '',
    iq: props.initialData?.iq || '',
    kategori_peserta_id: Array.isArray(props.initialData?.kategori_pesertas) && props.initialData.kategori_pesertas.length > 0
        ? (typeof props.initialData.kategori_pesertas[0] === 'object' 
            ? props.initialData.kategori_pesertas[0].id 
            : props.initialData.kategori_pesertas[0])
        : Array.isArray(props.initialData?.kategori_atlets) && props.initialData.kategori_atlets.length > 0
            ? (typeof props.initialData.kategori_atlets[0] === 'object' 
                ? props.initialData.kategori_atlets[0].id 
                : props.initialData.kategori_atlets[0])
            : '',
    is_active: props.initialData?.is_active !== undefined ? props.initialData.is_active : 1,
    foto: props.initialData?.foto || '',
    id: props.initialData?.id || undefined,
    file: null,
    is_delete_foto: 0,
    // Cabang Olahraga fields
    cabor_id: props.initialData?.cabor_id || '',
    posisi_atlet: props.initialData?.posisi_atlet || '',
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
            // Gunakan /api/cabor-list untuk mendapatkan semua cabor tanpa filter relasi
        const caborRes = await axios.get('/api/cabor-list');
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
                || props.initialData?.kategori_atlets
                || (page.props as any).kategori_pesertas 
                || (page.props as any).kategori_atlets
                || (page.props as any).item?.kategori_pesertas
                || (page.props as any).item?.kategori_atlets;
            
            if (existingKategori && Array.isArray(existingKategori) && existingKategori.length > 0) {
                const firstKategori = existingKategori[0];
                formData.value.kategori_peserta_id = typeof firstKategori === 'object' 
                    ? firstKategori.id 
                    : firstKategori;
            }

            // Load cabor data dari pivot jika ada
            const existingCabor = props.initialData?.cabor_id 
                || (page.props as any).item?.cabor_id;
            const existingPosisi = props.initialData?.posisi_atlet 
                || (page.props as any).item?.posisi_atlet;
            
            if (existingCabor) {
                formData.value.cabor_id = existingCabor;
            }
            if (existingPosisi) {
                formData.value.posisi_atlet = existingPosisi;
            }
        }
    } catch (e) {
        console.error('Gagal mengambil data kecamatan/kelurahan/kategori peserta/cabor', e);
        kecamatanOptions.value = [];
        kategoriPesertaOptions.value = [];
        caborOptions.value = [];
    }
});

// Watch untuk update kategori_peserta_id saat initialData berubah
watch(
    () => props.initialData?.kategori_pesertas || props.initialData?.kategori_atlets,
    (newKategori) => {
        if (props.mode === 'edit' && Array.isArray(newKategori) && newKategori.length > 0) {
            const firstKategori = newKategori[0];
            formData.value.kategori_peserta_id = typeof firstKategori === 'object' 
                ? firstKategori.id 
                : firstKategori;
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
    
    // Reset cabor_id jika cabor yang dipilih tidak ada di filtered list
    if (formData.value.cabor_id && !caborOptions.value.find((c: any) => c.value === formData.value.cabor_id)) {
        formData.value.cabor_id = '';
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

// Watch untuk reset field NPCI/SOIna jika kategori peserta berubah
watch(
    () => formData.value.kategori_peserta_id,
    (newVal, oldVal) => {
        // Jika kategori peserta berubah dan tidak lagi memilih NPCI atau SOIna, reset field terkait
        const currentKategori = selectedKategoriPeserta.value;
        if (!currentKategori) {
            // Reset field jika tidak ada NPCI atau SOIna yang dipilih
            const oldOption = kategoriPesertaOptions.value.find(opt => opt.value === oldVal);
            if (oldOption) {
                if (oldOption.label === 'NPCI') {
                    formData.value.klasifikasi = '';
                }
                if (oldOption.label === 'SOIna') {
                    formData.value.iq = '';
                }
                // Reset disabilitas hanya jika tidak ada NPCI atau SOIna yang dipilih
                formData.value.disabilitas = '';
            }
        }
    },
);

// Computed untuk mengecek apakah kategori peserta yang dipilih adalah NPCI atau SOIna
const selectedKategoriPeserta = computed(() => {
    const selectedId = formData.value.kategori_peserta_id;
    if (!selectedId) return null;
    
    const selectedOption = kategoriPesertaOptions.value.find(opt => opt.value === selectedId);
    if (!selectedOption) return null;
    
    if (selectedOption.label === 'NPCI') return 'NPCI';
    if (selectedOption.label === 'SOIna') return 'SOIna';
    return null;
});

const formInputs = computed(() => {
    const baseInputs = [
        {
            name: 'nik',
            label: 'NIK',
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
            name: 'kategori_peserta_id',
            label: 'Kategori Peserta',
            type: 'select' as const,
            placeholder: 'Pilih Kategori Peserta',
            required: true,
            options: kategoriPesertaOptions.value,
            help: 'Pilih kategori peserta',
        },
    ];
    
    // Tambahkan field khusus untuk NPCI tepat setelah kategori peserta
    if (selectedKategoriPeserta.value === 'NPCI') {
        const kategoriIndex = baseInputs.findIndex(input => input.name === 'kategori_peserta_id');
        baseInputs.splice(kategoriIndex + 1, 0,
            {
                name: 'disabilitas',
                label: 'Disabilitas',
                type: 'text' as const,
                placeholder: 'Masukkan jenis disabilitas',
                required: false,
            },
            {
                name: 'klasifikasi',
                label: 'Klasifikasi',
                type: 'text' as const,
                placeholder: 'Masukkan klasifikasi',
                required: false,
            }
        );
    }
    
    // Tambahkan field khusus untuk SOIna tepat setelah kategori peserta
    if (selectedKategoriPeserta.value === 'SOIna') {
        const kategoriIndex = baseInputs.findIndex(input => input.name === 'kategori_peserta_id');
        baseInputs.splice(kategoriIndex + 1, 0,
            {
                name: 'disabilitas',
                label: 'Disabilitas',
                type: 'text' as const,
                placeholder: 'Masukkan jenis disabilitas',
                required: false,
            },
            {
                name: 'iq',
                label: 'IQ',
                type: 'text' as const,
                placeholder: 'Masukkan IQ',
                required: false,
            }
        );
    }
    
    // Cabang Olahraga Section
    baseInputs.push(
        {
            name: 'cabor_id',
            label: 'Cabang Olahraga',
            type: 'select' as const,
            placeholder: 'Pilih Cabang Olahraga (Opsional)',
            required: false,
            options: caborOptions.value,
            help: 'Pilih cabang olahraga jika atlet sudah ditentukan cabornya',
        },
        {
            name: 'posisi_atlet',
            label: 'Posisi / Nomor / Kelas',
            type: 'text' as const,
            placeholder: 'Contoh: Striker, 100m, Kelas 55kg',
            required: false,
            help: 'Masukkan posisi, nomor pertandingan, atau kelas atlet',
        }
    );
    
    // Tambahkan field status dan foto
    baseInputs.push(
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
        { name: 'file', label: 'Foto', type: 'file' as const, placeholder: 'Upload foto' }
    );
    
    return baseInputs;
});

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
    if (field === 'kategori_peserta_id') {
        formData.value.kategori_peserta_id = value;
        // Reset field NPCI/SOIna jika kategori berubah
        const selectedOption = kategoriPesertaOptions.value.find(opt => opt.value === value);
        if (!selectedOption || (selectedOption.label !== 'NPCI' && selectedOption.label !== 'SOIna')) {
            formData.value.disabilitas = '';
            formData.value.klasifikasi = '';
            formData.value.iq = '';
        }
    }
}

const handleSave = (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    // Ambil kategori_peserta_id dari dataFromFormInput dan convert ke array untuk backend
    const kategoriPesertaId = dataFromFormInput.kategori_peserta_id || formData.value.kategori_peserta_id;
    const kategoriPesertaIds = kategoriPesertaId ? [kategoriPesertaId] : [];

    const formFields = {
        ...formData.value,
        ...dataFromFormInput,
        kategori_pesertas: kategoriPesertaIds, // Backend expects array
        // Backward compatibility
        kategori_atlets: kategoriPesertaIds,
        // Cabang Olahraga fields
        cabor_id: dataFromFormInput.cabor_id || formData.value.cabor_id || null,
        posisi_atlet: dataFromFormInput.posisi_atlet || formData.value.posisi_atlet || null,
    };

    // Jika user masih pending, jangan kirim is_active (biarkan tetap 0 sampai di-approve)
    if (isPendingRegistration.value) {
        delete formFields.is_active;
    }

    const url = '/atlet';

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
