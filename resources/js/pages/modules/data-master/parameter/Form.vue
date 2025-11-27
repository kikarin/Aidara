<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const kategori = ref(props.initialData?.kategori || 'kesehatan');

const formData = computed(() => {
    const base = {
        nama: props.initialData?.nama || '',
        satuan: props.initialData?.satuan || '',
        kategori: props.initialData?.kategori || 'kesehatan',
        nilai_target: props.initialData?.nilai_target || '',
        performa_arah: props.initialData?.performa_arah || 'max',
        id: props.initialData?.id || undefined,
    };
    return base;
});

// Base inputs yang selalu ada
const baseInputs = [
    {
        name: 'nama',
        label: 'Nama Parameter',
        type: 'text' as const,
        placeholder: 'Masukkan nama Parameter',
        required: true,
    },
    {
        name: 'satuan',
        label: 'Satuan',
        type: 'text' as const,
        placeholder: 'Masukkan satuan',
        required: true,
    },
    {
        name: 'kategori',
        label: 'Kategori',
        type: 'select' as const,
        options: [
            { value: 'kesehatan', label: 'Kesehatan (untuk semua peserta)' },
            { value: 'khusus', label: 'Khusus (hanya untuk atlet, dengan performa)' },
            { value: 'umum', label: 'Umum (untuk semua atlet, dengan performa)' },
        ],
        placeholder: 'Pilih kategori',
        required: true,
    },
];

// Dynamic inputs untuk kategori khusus/umum
const dynamicInputs = computed(() => {
    if (kategori.value === 'khusus' || kategori.value === 'umum') {
        return [
            {
                name: 'nilai_target',
                label: 'Nilai Target',
                type: 'text' as const,
                placeholder: 'Masukkan nilai target',
                required: true,
                help: 'Nilai target untuk menghitung persentase performa',
            },
            {
                name: 'performa_arah',
                label: 'Arah Performa',
                type: 'select' as const,
                options: [
                    { value: 'min', label: 'Semakin Kecil Semakin Baik (contoh: waktu lari)' },
                    { value: 'max', label: 'Semakin Besar Semakin Baik (contoh: berat angkat)' },
                ],
                placeholder: 'Pilih arah performa',
                required: true,
                help: 'Pilih apakah nilai yang lebih kecil atau lebih besar yang menunjukkan performa lebih baik',
            },
        ];
    }
    return [];
});

// Combine base dan dynamic inputs
const formInputs = computed(() => {
    return [...baseInputs, ...dynamicInputs.value];
});

// Watch kategori untuk update form inputs
watch(
    () => formData.value.kategori,
    (newVal) => {
        kategori.value = newVal;
    },
);

// Handle field update dari FormInput
const handleFieldUpdate = ({ field, value }: { field: string; value: any }) => {
    if (field === 'kategori') {
        kategori.value = value;
    }
};

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        nama: form.nama,
        satuan: form.satuan,
        kategori: form.kategori || 'kesehatan',
    };

    // Hanya tambahkan nilai_target dan performa_arah jika kategori khusus atau umum
    if (form.kategori === 'khusus' || form.kategori === 'umum') {
        dataToSave.nilai_target = form.nilai_target || '';
        dataToSave.performa_arah = form.performa_arah || 'max';
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }

    save(dataToSave, {
        url: '/data-master/parameter',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Data Parameter berhasil ditambahkan' : 'Data Parameter berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data Parameter' : 'Gagal memperbarui data Parameter',
        redirectUrl: '/data-master/parameter',
    });
};
</script>

<template>
    <FormInput
        :key="`parameter-form-${kategori}`"
        :form-inputs="formInputs"
        :initial-data="formData"
        @save="handleSave"
        @field-updated="handleFieldUpdate"
    />
</template>
