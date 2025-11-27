<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    get_CategoryPermission?: Record<number, string>;
    onBack?: () => void;
}>();

const formInputs = [
    {
        name: 'name',
        label: 'Permission Name',
        type: 'text' as const,
        placeholder: 'Users Create',
        required: true,
    },
    ...(props.get_CategoryPermission
        ? [
              {
                  name: 'category_permission_id',
                  label: 'Category Permission',
                  type: 'select' as const,
                  options: Object.entries(props.get_CategoryPermission).map(([id, name]) => ({ value: Number(id), label: name })),
                  required: true,
                  placeholder: 'Pilih Kategori',
              },
          ]
        : []),
    ...(props.mode === 'create'
        ? [
              {
                  name: 'is_crud',
                  label: 'Buatkan CRUD',
                  type: 'radio' as const,
                  options: [
                      { value: 'ya', label: 'Ya' },
                      { value: 'tidak', label: 'Tidak' },
                  ],
                  help: 'Jika Ya, akan dibuatkan 5 permission CRUD (Show, Detail, Add, Edit, Delete) otomatis.',
              },
          ]
        : []),
];

const handleSave = (data: Record<string, any>) => {
    if (props.mode === 'create') {
        router.post('/menu-permissions/permissions/store-permission', data, {
            onSuccess: () => {
                toast({ title: 'Berhasil menambahkan permission', variant: 'success' });
                router.visit('/menu-permissions/permissions');
            },
            onError: (errors) => {
                Object.entries(errors).forEach(([field, message]) => {
                    toast({ title: `${field}: ${message}`, variant: 'destructive' });
                });
            },
        });
    } else {
        router.put(`/menu-permissions/permissions/update-permission/${props.initialData?.id}`, data, {
            onSuccess: () => {
                toast({ title: 'Berhasil memperbarui permission', variant: 'success' });
                router.visit('/menu-permissions/permissions');
            },
            onError: (errors) => {
                Object.entries(errors).forEach(([field, message]) => {
                    toast({ title: `${field}: ${message}`, variant: 'destructive' });
                });
            },
        });
    }
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="initialData" @save="handleSave" @cancel="props.onBack" />
</template>
