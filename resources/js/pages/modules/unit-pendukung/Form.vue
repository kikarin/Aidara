<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    jenisUnitPendukungs?: Array<{ id: number; nama: string }>;
}>();

const formData = computed(() => {
    const base = {
        nama: props.initialData?.nama || '',
        jenis_unit_pendukung_id: props.initialData?.jenis_unit_pendukung_id || '',
        deskripsi: props.initialData?.deskripsi || '',
        id: props.initialData?.id || undefined,
    };
    return base;
});

const formInputs = [
    {
        name: 'nama',
        label: 'Nama Unit Pendukung',
        type: 'text' as const,
        placeholder: 'Masukkan nama unit pendukung',
        required: true,
    },
    {
        name: 'jenis_unit_pendukung_id',
        label: 'Jenis Unit Pendukung',
        type: 'select' as const,
        placeholder: 'Pilih jenis unit pendukung',
        required: true,
        options:
            props.jenisUnitPendukungs?.map((item) => ({
                value: item.id,
                label: item.nama,
            })) || [],
    },
    {
        name: 'deskripsi',
        label: 'Deskripsi',
        type: 'textarea' as const,
        placeholder: 'Masukkan deskripsi unit pendukung',
        required: false,
    },
];

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        nama: form.nama,
        jenis_unit_pendukung_id: form.jenis_unit_pendukung_id,
        deskripsi: form.deskripsi,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }

    save(dataToSave, {
        url: '/unit-pendukung',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Data unit pendukung berhasil ditambahkan' : 'Data unit pendukung berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan data unit pendukung' : 'Gagal memperbarui data unit pendukung',
        redirectUrl: '/unit-pendukung',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
