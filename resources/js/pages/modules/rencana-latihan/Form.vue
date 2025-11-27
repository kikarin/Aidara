<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { ref, watch } from 'vue';
import SelectTableMultiple from '../components/SelectTableMultiple.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    infoHeader: any;
}>();

const calculateAge = (birthDate: string | null | undefined): number | string => {
    if (!birthDate) return '-';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};

function getLamaBergabung(tanggalBergabung: string) {
    if (!tanggalBergabung) return '-';
    const start = new Date(tanggalBergabung);
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
}

const formData = ref({
    tanggal: props.initialData?.tanggal || '',
    materi: props.initialData?.materi || '',
    lokasi_latihan: props.initialData?.lokasi_latihan || '',
    catatan: props.initialData?.catatan || '',
    target_latihan_ids: props.initialData?.target_latihan?.map((t: any) => t.id) || [],
    atlet_ids: props.initialData?.atlets?.map((a: any) => a.id) || [],
    pelatih_ids: props.initialData?.pelatihs?.map((p: any) => p.id) || [],
    tenaga_pendukung_ids: props.initialData?.tenaga_pendukung?.map((t: any) => t.id) || [],
});

let initialized = false;
watch(
    () => props.initialData,
    (newVal) => {
        if (!initialized && newVal) {
            formData.value = {
                tanggal: newVal.tanggal || '',
                materi: newVal.materi || '',
                lokasi_latihan: newVal.lokasi_latihan || '',
                catatan: newVal.catatan || '',
                target_latihan_ids: newVal.target_latihan?.map((t: any) => t.id) || [],
                atlet_ids: newVal.atlets?.map((a: any) => a.id) || [],
                pelatih_ids: newVal.pelatihs?.map((p: any) => p.id) || [],
                tenaga_pendukung_ids: newVal.tenaga_pendukung?.map((t: any) => t.id) || [],
            };
            initialized = true;
        }
    },
    { immediate: true, deep: true },
);

const handleSave = (form: any) => {
    // Gunakan data dari parameter form (hasil emit dari FormInput)
    const dataToSave: Record<string, any> = {
        tanggal: form.tanggal,
        materi: form.materi,
        lokasi_latihan: form.lokasi_latihan,
        catatan: form.catatan,
        target_latihan_ids: form.target_latihan_ids,
        atlet_ids: form.atlet_ids,
        pelatih_ids: form.pelatih_ids,
        tenaga_pendukung_ids: form.tenaga_pendukung_ids,
        program_latihan_id: props.infoHeader?.program_latihan_id,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: `/program-latihan/${props.infoHeader?.program_latihan_id}/rencana-latihan`,
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Rencana latihan berhasil ditambahkan' : 'Rencana latihan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan rencana latihan' : 'Gagal memperbarui rencana latihan',
        redirectUrl: `/program-latihan/${props.infoHeader?.program_latihan_id}/rencana-latihan`,
    });
};

const columnsAtlet = [
    {
        key: 'foto',
        label: 'Foto',
        format: (row: any) =>
            row.foto
                ? `<div class='cursor-pointer' onclick="window.open('${row.foto}', '_blank')"><img src='${row.foto}' alt='Foto' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' /></div>`
                : "<div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs'>No</div>",
    },
    { key: 'nama', label: 'Nama' },
    { key: 'posisi_atlet_nama', label: 'Posisi' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            return calculateAge(row.tanggal_lahir);
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => getLamaBergabung(row.tanggal_bergabung),
    },
];
const columnsPelatih = [
    {
        key: 'foto',
        label: 'Foto',
        format: (row: any) =>
            row.foto
                ? `<div class='cursor-pointer' onclick="window.open('${row.foto}', '_blank')"><img src='${row.foto}' alt='Foto' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' /></div>`
                : "<div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs'>No</div>",
    },
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            return calculateAge(row.tanggal_lahir);
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => getLamaBergabung(row.tanggal_bergabung),
    },
];
const columnsTenagaPendukung = [
    {
        key: 'foto',
        label: 'Foto',
        format: (row: any) =>
            row.foto
                ? `<div class='cursor-pointer' onclick="window.open('${row.foto}', '_blank')"><img src='${row.foto}' alt='Foto' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' /></div>`
                : "<div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs'>No</div>",
    },
    { key: 'nama', label: 'Nama' },
    { key: 'jenis_tenaga_pendukung_nama', label: 'Jenis Tenaga Pendukung' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            return calculateAge(row.tanggal_lahir);
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => getLamaBergabung(row.tanggal_bergabung),
    },
];
</script>

<template>
    <div class="space-y-6">
        <FormInput
            :form-inputs="[
                { name: 'tanggal', label: 'Tanggal', type: 'date', required: true },
                { name: 'materi', label: 'Materi', type: 'textarea', required: true },
                { name: 'lokasi_latihan', label: 'Lokasi Latihan', type: 'text', required: true },
                { name: 'catatan', label: 'Catatan', type: 'textarea', required: false },
            ]"
            :initial-data="formData"
            @save="handleSave"
        >
            <template #custom-fields>
                <SelectTableMultiple
                    v-model:selected-ids="formData.target_latihan_ids"
                    label="Target Latihan"
                    :endpoint="`/api/target-latihan?program_latihan_id=${props.infoHeader?.program_latihan_id}`"
                    :columns="[
                        { key: 'deskripsi', label: 'Deskripsi' },
                        { key: 'peruntukan', label: 'Peruntukan' },
                        { key: 'jenis_target', label: 'Jenis' },
                    ]"
                    id-key="id"
                    name-key="deskripsi"
                    :auto-select-all="props.mode === 'create'"
                />
                <SelectTableMultiple
                    v-model:selected-ids="formData.atlet_ids"
                    label="Atlet"
                    :endpoint="`/api/cabor-kategori-atlet?cabor_kategori_id=${props.infoHeader?.cabor_kategori_id}`"
                    :columns="columnsAtlet"
                    id-key="atlet_id"
                    name-key="atlet_nama"
                    :auto-select-all="props.mode === 'create'"
                />
                <SelectTableMultiple
                    v-model:selected-ids="formData.pelatih_ids"
                    label="Pelatih"
                    :endpoint="`/api/cabor-kategori-pelatih?cabor_kategori_id=${props.infoHeader?.cabor_kategori_id}`"
                    :columns="columnsPelatih"
                    id-key="pelatih_id"
                    name-key="pelatih_nama"
                    :auto-select-all="props.mode === 'create'"
                />
                <SelectTableMultiple
                    v-model:selected-ids="formData.tenaga_pendukung_ids"
                    label="Tenaga Pendukung"
                    :endpoint="`/api/cabor-kategori-tenaga-pendukung?cabor_kategori_id=${props.infoHeader?.cabor_kategori_id}`"
                    :columns="columnsTenagaPendukung"
                    id-key="tenaga_pendukung_id"
                    name-key="tenaga_pendukung_nama"
                    :auto-select-all="props.mode === 'create'"
                />
            </template>
        </FormInput>
    </div>
</template>
