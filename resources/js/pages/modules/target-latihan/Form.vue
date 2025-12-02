<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    infoHeader?: any;
}>();

const formData = computed(() => ({
    deskripsi: props.initialData?.deskripsi || '',
    satuan: props.initialData?.satuan || '',
    nilai_target: props.initialData?.nilai_target || '',
    peruntukan: props.initialData?.peruntukan || (props.infoHeader?.jenis_target === 'individu' ? 'atlet' : null),
    performa_arah: props.initialData?.performa_arah || 'max',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => {
    const baseInputs: any[] = [
        {
            name: 'deskripsi',
            label: 'Deskripsi Target',
            type: 'text',
            placeholder: 'Masukkan deskripsi target',
            required: true,
        },
        {
            name: 'satuan',
            label: 'Satuan',
            type: 'text',
            placeholder: 'Masukkan satuan (opsional)',
            required: false,
        },
        {
            name: 'nilai_target',
            label: 'Nilai Target',
            type: 'text',
            placeholder: 'Masukkan nilai target (opsional)',
            required: false,
        },
        {
            name: 'performa_arah',
            label: 'Arah Performa',
            type: 'select',
            options: [
                { value: 'min', label: 'Semakin Kecil Semakin Baik (contoh: waktu lari)' },
                { value: 'max', label: 'Semakin Besar Semakin Baik (contoh: berat angkat)' },
            ],
            placeholder: 'Pilih arah performa',
            required: false,
            helpText: 'Pilih apakah nilai yang lebih kecil atau lebih besar yang menunjukkan performa lebih baik',
        },
    ];

    // Peruntukan hanya untuk target individu
    if (props.infoHeader?.jenis_target === 'individu') {
        baseInputs.splice(1, 0, {
            name: 'peruntukan',
            label: 'Peruntukan',
            type: 'select',
            options: [
                { value: 'atlet', label: 'Atlet' },
                { value: 'pelatih', label: 'Pelatih' },
                { value: 'tenaga-pendukung', label: 'Tenaga Pendukung' },
            ],
            placeholder: 'Pilih peruntukan',
            required: true,
        });
    }

    return baseInputs;
});

const handleSave = (form: any) => {
    const dataToSave: Record<string, any> = {
        deskripsi: form.deskripsi,
        satuan: form.satuan,
        nilai_target: form.nilai_target,
        peruntukan: form.peruntukan,
        performa_arah: form.performa_arah || 'max',
        program_latihan_id: props.infoHeader?.program_latihan_id,
        jenis_target: props.infoHeader?.jenis_target,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/target-latihan',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Target latihan berhasil ditambahkan' : 'Target latihan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan target latihan' : 'Gagal memperbarui target latihan',
        redirectUrl: `/program-latihan/${props.infoHeader?.program_latihan_id}/target-latihan/${props.infoHeader?.jenis_target}`,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
