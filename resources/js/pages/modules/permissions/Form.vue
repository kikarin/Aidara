<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    get_CategoryPermission?: Record<number, string>;
    onBack?: () => void;
}>();

const formInputs = [
    {
        name: 'name',
        label: 'Name Permission Category',
        type: 'text' as const,
        placeholder: 'Enter permission category name',
        required: true,
    },
    ...(props.get_CategoryPermission
        ? [
              {
                  name: 'parent_id',
                  label: 'Parent Category',
                  type: 'select' as const,
                  options: Object.entries(props.get_CategoryPermission).map(([id, name]) => ({ value: Number(id), label: name })),
                  required: false,
                  placeholder: 'Pilih Parent (opsional)',
              },
          ]
        : []),
    {
        name: 'sequence',
        label: 'Sequence',
        type: 'number' as const,
        placeholder: 'Enter display order',
        required: true,
    },
];

const handleSave = (data: Record<string, any>) => {
    save(data, {
        url: '/menu-permissions/permissions',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Kategori Permission berhasil ditambahkan' : 'Kategori Permission berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Kategori Gagal menambahkan permission' : 'Kategori Gagal memperbarui permission',
        redirectUrl: '/menu-permissions/permissions',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" @cancel="props.onBack" />
</template>
