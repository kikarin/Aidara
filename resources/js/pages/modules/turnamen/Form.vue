<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import SelectTableMultiple from '@/pages/modules/components/SelectTableMultiple.vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = computed(() => {
    const base = {
        nama: props.initialData?.nama || '',
        foto: null, // Jangan set dari initialData karena itu string path, bukan File object
        cabor_kategori_id: props.initialData?.cabor_kategori_id || '',
        tanggal_mulai: props.initialData?.tanggal_mulai || '',
        tanggal_selesai: props.initialData?.tanggal_selesai || '',
        tingkat_id: props.initialData?.tingkat_id || '',
        lokasi: props.initialData?.lokasi || '',
        juara_id: props.initialData?.juara_id || '',
        hasil: props.initialData?.hasil || '',
        evaluasi: props.initialData?.evaluasi || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = [
    {
        name: 'nama',
        label: 'Nama Turnamen',
        type: 'text' as const,
        placeholder: 'Masukkan nama turnamen',
        required: true,
    },
    {
        name: 'foto',
        label: 'Foto Turnamen',
        type: 'file' as const,
        placeholder: 'Upload foto turnamen',
        required: false,
        help: 'Format: JPG, PNG. Maksimal 5MB.',
    },
    {
        name: 'cabor_kategori_id',
        label: 'Cabor Kategori',
        type: 'select' as const,
        placeholder: 'Pilih cabor kategori',
        required: true,
        options: [],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        name: 'tanggal_mulai',
        label: 'Tanggal Mulai',
        type: 'date' as const,
        placeholder: 'Pilih tanggal mulai',
        required: true,
    },
    {
        name: 'tanggal_selesai',
        label: 'Tanggal Selesai',
        type: 'date' as const,
        placeholder: 'Pilih tanggal selesai',
        required: true,
    },
    {
        name: 'tingkat_id',
        label: 'Tingkat',
        type: 'select' as const,
        placeholder: 'Pilih tingkat',
        required: true,
        options: [],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        name: 'lokasi',
        label: 'Lokasi',
        type: 'text' as const,
        placeholder: 'Masukkan lokasi turnamen',
        required: true,
    },
    {
        name: 'juara_id',
        label: 'Juara',
        type: 'select' as const,
        placeholder: 'Pilih juara (opsional)',
        required: false,
        options: [],
        optionLabel: 'label',
        optionValue: 'value',
    },
    {
        name: 'hasil',
        label: 'Hasil',
        type: 'textarea' as const,
        placeholder: 'Masukkan hasil turnamen (opsional)',
        required: false,
    },
    {
        name: 'evaluasi',
        label: 'Evaluasi',
        type: 'textarea' as const,
        placeholder: 'Masukkan evaluasi turnamen (opsional)',
        required: false,
    },
];

// Data untuk peserta
const selectedAtletIds = ref<number[]>([]);
const selectedPelatihIds = ref<number[]>([]);
const selectedTenagaPendukungIds = ref<number[]>([]);
const currentCaborKategoriId = ref<string>('');

const loadExistingPeserta = async () => {
    if (props.mode === 'edit' && props.initialData?.id) {
        try {
            const atletResponse = await axios.get(`/web-api/turnamen/${props.initialData.id}/peserta?jenis_peserta=atlet&per_page=-1`);
            selectedAtletIds.value = atletResponse.data.data.map((item: any) => item.id);

            const pelatihResponse = await axios.get(`/web-api/turnamen/${props.initialData.id}/peserta?jenis_peserta=pelatih&per_page=-1`);
            selectedPelatihIds.value = pelatihResponse.data.data.map((item: any) => item.id);

            const tenagaPendukungResponse = await axios.get(
                `/web-api/turnamen/${props.initialData.id}/peserta?jenis_peserta=tenaga-pendukung&per_page=-1`,
            );
            selectedTenagaPendukungIds.value = tenagaPendukungResponse.data.data.map((item: any) => item.id);

            if (props.initialData?.cabor_kategori_id) {
                currentCaborKategoriId.value = props.initialData.cabor_kategori_id.toString();
            }
        } catch (error) {
            console.error('Error loading existing peserta:', error);
        }
    }
};

const loadSelectOptions = async () => {
    try {
        const caborKategoriResponse = await axios.get('/api/cabor-kategori');
        const caborKategoriOptions = caborKategoriResponse.data.data.map((item: any) => ({
            label: `${item.cabor_nama} - ${item.nama}`,
            value: item.id,
        }));
        formInputs.find((input) => input.name === 'cabor_kategori_id')!.options = caborKategoriOptions;

        const tingkatResponse = await axios.get('/api/tingkat');
        const tingkatOptions = tingkatResponse.data.data.map((item: any) => ({
            label: item.nama,
            value: item.id,
        }));
        formInputs.find((input) => input.name === 'tingkat_id')!.options = tingkatOptions;

        const juaraResponse = await axios.get('/api/juara');
        const juaraOptions = juaraResponse.data.data.map((item: any) => ({
            label: item.nama,
            value: item.id,
        }));
        formInputs.find((input) => input.name === 'juara_id')!.options = juaraOptions;
    } catch (error) {
        console.error('Error loading select options:', error);
    }
};

loadSelectOptions();
loadExistingPeserta();

watch(currentCaborKategoriId, (newValue) => {
    if (newValue && props.mode === 'create') {
        selectedAtletIds.value = [];
        selectedPelatihIds.value = [];
        selectedTenagaPendukungIds.value = [];
    }
});

const handleFieldUpdate = (data: { field: string; value: any }) => {
    if (data.field === 'cabor_kategori_id') {
        currentCaborKategoriId.value = data.value;
    }
};

const handleSave = (form: any, setFormErrors?: (errors: Record<string, string>) => void) => {
    console.log('handleSave - form data:', form);
    console.log('handleSave - form.foto:', form.foto);
    console.log('handleSave - form.foto instanceof File:', form.foto instanceof File);
    
    const dataToSave: Record<string, any> = {
        nama: form.nama,
        tanggal_mulai: form.tanggal_mulai,
        tanggal_selesai: form.tanggal_selesai,
        tingkat_id: form.tingkat_id,
        lokasi: form.lokasi,
        juara_id: form.juara_id || null,
        hasil: form.hasil,
        evaluasi: form.evaluasi,
    };

    // Tambahkan foto jika ada (bisa berupa File object)
    // FormInput akan menyimpan File object ke form.foto saat user upload file
    if (form.foto instanceof File) {
        console.log('Adding foto to dataToSave:', {
            name: form.foto.name,
            size: form.foto.size,
            type: form.foto.type,
        });
        dataToSave.foto = form.foto;
    } else {
        console.log('Foto is not a File instance, type:', typeof form.foto, 'value:', form.foto);
    }
    
    console.log('dataToSave before save:', dataToSave);
    console.log('dataToSave has foto:', 'foto' in dataToSave);
    console.log('dataToSave.foto instanceof File:', dataToSave.foto instanceof File);

    if (form.cabor_kategori_id) {
        dataToSave.cabor_kategori_id = form.cabor_kategori_id;
    }

    if (props.mode === 'create') {
        dataToSave.peserta_data = {
            atlet_ids: selectedAtletIds.value,
            pelatih_ids: selectedPelatihIds.value,
            tenaga_pendukung_ids: selectedTenagaPendukungIds.value,
        };
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }

    save(dataToSave, {
        url: '/turnamen',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Data turnamen berhasil ditambahkan' : 'Data turnamen berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data turnamen' : 'Gagal memperbarui data turnamen',
        redirectUrl: '/turnamen',
        setFormErrors: setFormErrors,
    });
};

// Columns untuk SelectTableMultiple
const atletColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'posisi_atlet_nama', label: 'Posisi' },
    { key: 'jenis_kelamin', label: 'Jenis Kelamin', format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            if (!row.tanggal_lahir) return '-';
            const today = new Date();
            const birth = new Date(row.tanggal_lahir);
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => {
            if (!row.tanggal_bergabung) return '-';
            const start = new Date(row.tanggal_bergabung);
            const now = new Date();
            let tahun = now.getFullYear() - start.getFullYear();
            let bulan = now.getMonth() - start.getMonth();
            if (bulan < 0) {
                tahun--;
                bulan += 12;
            }
            let result = '';
            if (tahun > 0) result += tahun + ' tahun ';
            if (bulan > 0) result += bulan + ' bulan';
            if (!result) result = 'Kurang dari 1 bulan';
            return result.trim();
        },
    },
];

const pelatihColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih' },
    { key: 'jenis_kelamin', label: 'Jenis Kelamin', format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            if (!row.tanggal_lahir) return '-';
            const today = new Date();
            const birth = new Date(row.tanggal_lahir);
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => {
            if (!row.tanggal_bergabung) return '-';
            const start = new Date(row.tanggal_bergabung);
            const now = new Date();
            let tahun = now.getFullYear() - start.getFullYear();
            let bulan = now.getMonth() - start.getMonth();
            if (bulan < 0) {
                tahun--;
                bulan += 12;
            }
            let result = '';
            if (tahun > 0) result += tahun + ' tahun ';
            if (bulan > 0) result += bulan + ' bulan';
            if (!result) result = 'Kurang dari 1 bulan';
            return result.trim();
        },
    },
];

const tenagaPendukungColumns = [
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_tenaga_pendukung_nama', label: 'Jenis Tenaga Pendukung' },
    { key: 'jenis_kelamin', label: 'Jenis Kelamin', format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            if (!row.tanggal_lahir) return '-';
            const today = new Date();
            const birth = new Date(row.tanggal_lahir);
            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => {
            if (!row.tanggal_bergabung) return '-';
            const start = new Date(row.tanggal_bergabung);
            const now = new Date();
            let tahun = now.getFullYear() - start.getFullYear();
            let bulan = now.getMonth() - start.getMonth();
            if (bulan < 0) {
                tahun--;
                bulan += 12;
            }
            let result = '';
            if (tahun > 0) result += tahun + ' tahun ';
            if (bulan > 0) result += bulan + ' bulan';
            if (!result) result = 'Kurang dari 1 bulan';
            return result.trim();
        },
    },
];

// Filter form inputs berdasarkan mode
const filteredFormInputs = computed(() => {
    if (props.mode === 'edit') {
        return formInputs.filter((input) => input.name !== 'cabor_kategori_id');
    }
    return formInputs;
});
</script>

<template>
    <div class="space-y-6">
        <FormInput :form-inputs="filteredFormInputs" :initial-data="formData" @field-updated="handleFieldUpdate" @save="handleSave" />

        <div v-if="props.mode === 'create'" class="space-y-6">
            <h3 class="text-lg font-semibold">Peserta Turnamen</h3>

            <!-- Atlet Selection -->
            <div v-if="currentCaborKategoriId">
                <SelectTableMultiple
                    label="Pilih Atlet"
                    :endpoint="`/web-api/turnamen/peserta-by-cabor-kategori?cabor_kategori_id=${currentCaborKategoriId}&jenis_peserta=atlet`"
                    :columns="atletColumns"
                    id-key="id"
                    name-key="nama"
                    :selected-ids="selectedAtletIds"
                    @update:selected-ids="(ids: number[]) => (selectedAtletIds = ids)"
                />
            </div>

            <!-- Pelatih Selection -->
            <div v-if="currentCaborKategoriId">
                <SelectTableMultiple
                    label="Pilih Pelatih"
                    :endpoint="`/web-api/turnamen/peserta-by-cabor-kategori?cabor_kategori_id=${currentCaborKategoriId}&jenis_peserta=pelatih`"
                    :columns="pelatihColumns"
                    id-key="id"
                    name-key="nama"
                    :selected-ids="selectedPelatihIds"
                    @update:selected-ids="(ids: number[]) => (selectedPelatihIds = ids)"
                />
            </div>

            <!-- Tenaga Pendukung Selection -->
            <div v-if="currentCaborKategoriId">
                <SelectTableMultiple
                    label="Pilih Tenaga Pendukung"
                    :endpoint="`/web-api/turnamen/peserta-by-cabor-kategori?cabor_kategori_id=${currentCaborKategoriId}&jenis_peserta=tenaga-pendukung`"
                    :columns="tenagaPendukungColumns"
                    id-key="id"
                    name-key="nama"
                    :selected-ids="selectedTenagaPendukungIds"
                    @update:selected-ids="(ids: number[]) => (selectedTenagaPendukungIds = ids)"
                />
            </div>
        </div>
    </div>
</template>
