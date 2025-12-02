<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { save } = useHandleFormSave();
const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const mstParameters = computed(() => page.props.mstParameters || []);
const pemeriksaanId = computed(() => pemeriksaan.value.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formInitialData = computed(() => ({
    mst_parameter_id: props.initialData?.mst_parameter_id || '',
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'mst_parameter_id',
        label: 'Parameter',
        type: 'select' as const,
        required: true,
        options: mstParameters.value.map((param: any) => ({
            value: param.id,
            label: `${param.nama} (${param.satuan})`,
        })),
        placeholder: 'Pilih Parameter',
    },
]);

const handleSave = (form: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const dataToSave: Record<string, any> = {
        ...form,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter`,
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Parameter pemeriksaan berhasil ditambahkan' : 'Parameter pemeriksaan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan parameter pemeriksaan' : 'Gagal memperbarui parameter pemeriksaan',
        redirectUrl: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter`,
        onError: setFormErrors,
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formInitialData" @save="handleSave" />
</template>
