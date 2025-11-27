<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SelectTableMultiple from '../components/SelectTableMultiple.vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: any;
    pemeriksaan: any;
    jenisPeserta?: string;
}>();

console.log('pemeriksaan:', props.pemeriksaan);
console.log('cabor_kategori_id:', props.pemeriksaan?.cabor_kategori_id);

usePage();
const jenisPeserta = computed(() => props.jenisPeserta || 'atlet');

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

// Mapping config untuk SelectTableMultiple
const pesertaConfig = computed(() => {
    if (jenisPeserta.value === 'pelatih') {
        return {
            label: 'Pelatih',
            endpoint: `/api/cabor-kategori-pelatih?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}`,
            idKey: 'pelatih_id',
            nameKey: 'pelatih_nama',
            stateKey: 'pelatih_ids',
        };
    }
    if (jenisPeserta.value === 'tenaga-pendukung') {
        return {
            label: 'Tenaga Pendukung',
            endpoint: `/api/cabor-kategori-tenaga-pendukung?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}`,
            idKey: 'tenaga_pendukung_id',
            nameKey: 'tenaga_pendukung_nama',
            stateKey: 'tenaga_pendukung_ids',
        };
    }
    // Fallback: atlet
    return {
        label: 'Atlet',
        endpoint: `/api/cabor-kategori-atlet?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}`,
        idKey: 'atlet_id',
        nameKey: 'atlet_nama',
        stateKey: 'atlet_ids',
    };
});

interface SelectionState {
    atlet_ids: number[];
    pelatih_ids: number[];
    tenaga_pendukung_ids: number[];
    [key: string]: number[];
}

const selectionState = ref<SelectionState>({
    atlet_ids: [],
    pelatih_ids: [],
    tenaga_pendukung_ids: [],
});

// Set initial selection sesuai jenis peserta
if (props.mode === 'create' && props.initialData) {
    if (jenisPeserta.value === 'atlet') {
        selectionState.value.atlet_ids = props.initialData?.atlets?.map((a: any) => a.id) || [];
    } else if (jenisPeserta.value === 'pelatih') {
        selectionState.value.pelatih_ids = props.initialData?.pelatihs?.map((p: any) => p.id) || [];
    } else if (jenisPeserta.value === 'tenaga-pendukung') {
        selectionState.value.tenaga_pendukung_ids = props.initialData?.tenaga_pendukung?.map((t: any) => t.id) || [];
    }
}

const formState = ref({
    ref_status_pemeriksaan_id: props.initialData?.ref_status_pemeriksaan_id || '',
    catatan_umum: props.initialData?.catatan_umum || '',
    peserta_nama: props.initialData?.peserta?.nama || '',
    peserta_tipe: props.initialData?.peserta_type?.split('\\').pop() || '',
});

const formInputs = computed(() => {
    const inputs: any[] = [];

    // Hanya tampilkan form status dan catatan di mode edit
    if (props.mode === 'edit') {
        inputs.push(
            {
                name: 'ref_status_pemeriksaan_id',
                label: 'Status Pemeriksaan',
                type: 'select' as const,
                required: true,
                options: ((usePage().props.ref_status_pemeriksaan as any[]) || []).map((s) => ({ value: s.id, label: s.nama })),
            },
            {
                name: 'catatan_umum',
                label: 'Catatan Umum',
                type: 'textarea' as const,
            },
        );
    }

    return inputs;
});

const { save } = useHandleFormSave();

const handleSave = (form: any) => {
    let dataToSave;
    const url = `/pemeriksaan/${props.pemeriksaan.id}/peserta`;

    if (props.mode === 'create') {
        dataToSave = { ...form, [pesertaConfig.value.stateKey]: selectionState.value[pesertaConfig.value.stateKey] };
        save(dataToSave, {
            url,
            mode: 'create',
            redirectUrl: `/pemeriksaan/${props.pemeriksaan.id}/peserta?jenis_peserta=${jenisPeserta.value}`,
        });
    } else {
        dataToSave = {
            ref_status_pemeriksaan_id: form.ref_status_pemeriksaan_id,
            catatan_umum: form.catatan_umum,
            peserta_id: props.initialData.peserta_id,
            peserta_type: props.initialData.peserta_type,
        };
    }

    save(dataToSave, {
        url: props.mode === 'edit' ? `${url}/${props.initialData.id}` : url,
        mode: props.mode,
        redirectUrl: `/pemeriksaan/${props.pemeriksaan.id}/peserta?jenis_peserta=${jenisPeserta.value}`,
    });
};

const columns = computed(() => {
    const baseColumns = [
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

    const fotoColumn = {
        key: 'foto',
        label: 'Foto',
        format: (row: any) =>
            row.foto
                ? `<div class='cursor-pointer' onclick=\"window.open('${row.foto}', '_blank')\"><img src='${row.foto}' alt='Foto' class='w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow' /></div>`
                : "<div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs'>No</div>",
    };

    if (jenisPeserta.value === 'pelatih') {
        return [fotoColumn, { key: 'pelatih_nama', label: 'Nama Pelatih' }, { key: 'jenis_pelatih_nama', label: 'Jenis Pelatih' }, ...baseColumns];
    } else if (jenisPeserta.value === 'tenaga-pendukung') {
        return [
            fotoColumn,
            { key: 'tenaga_pendukung_nama', label: 'Nama Tenaga Pendukung' },
            { key: 'jenis_tenaga_pendukung_nama', label: 'Jenis Tenaga Pendukung' },
            ...baseColumns,
        ];
    } else {
        // Atlet
        return [fotoColumn, { key: 'atlet_nama', label: 'Nama Atlet' }, { key: 'posisi_atlet_nama', label: 'Posisi' }, ...baseColumns];
    }
});
</script>

<template>
    <FormInput :form-inputs="formInputs" @save="handleSave" :initial-data="formState">
        <template #custom-fields v-if="mode === 'create'">
            <SelectTableMultiple
                v-show="jenisPeserta === 'atlet'"
                :key="'atlet'"
                v-model:selected-ids="selectionState.atlet_ids"
                label="Atlet"
                :endpoint="`/api/cabor-kategori-atlet/available-for-pemeriksaan?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}&pemeriksaan_id=${props.pemeriksaan.id}`"
                :columns="columns"
                id-key="atlet_id"
                name-key="atlet_nama"
                :auto-select-all="true"
            />
            <SelectTableMultiple
                v-show="jenisPeserta === 'pelatih'"
                :key="'pelatih'"
                v-model:selected-ids="selectionState.pelatih_ids"
                label="Pelatih"
                :endpoint="`/api/cabor-kategori-pelatih/available-for-pemeriksaan?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}&pemeriksaan_id=${props.pemeriksaan.id}`"
                :columns="columns"
                id-key="pelatih_id"
                name-key="pelatih_nama"
                :auto-select-all="true"
            />
            <SelectTableMultiple
                v-show="jenisPeserta === 'tenaga-pendukung'"
                :key="'tenaga-pendukung'"
                v-model:selected-ids="selectionState.tenaga_pendukung_ids"
                label="Tenaga Pendukung"
                :endpoint="`/api/cabor-kategori-tenaga-pendukung/available-for-pemeriksaan?cabor_kategori_id=${props.pemeriksaan.cabor_kategori_id}&pemeriksaan_id=${props.pemeriksaan.id}`"
                :columns="columns"
                id-key="tenaga_pendukung_id"
                name-key="tenaga_pendukung_nama"
                :auto-select-all="true"
            />
        </template>
    </FormInput>
</template>
