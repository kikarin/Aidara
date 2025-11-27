<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    roles: Record<number, string>;
    selectedRoles?: number[];
}>();

const roleOptions = Object.entries(props.roles).map(([id, name]) => ({
    value: Number(id),
    label: name,
}));

const formData = computed(() => {
    const base = {
        name: props.initialData?.name || '',
        email: props.initialData?.email || '',
        password: '',
        password_confirmation: '',
        no_hp: props.initialData?.no_hp || '',
        role_id: props.selectedRoles || [],
        is_active: props.initialData?.is_active !== undefined ? props.initialData.is_active : 1,
        id: props.initialData?.id || undefined,
    };

    return base;
});

// State untuk toggle password visibility
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const formInputs = [
    {
        name: 'name',
        label: 'Name',
        type: 'text' as const,
        placeholder: 'Enter name',
        required: true,
    },
    {
        name: 'email',
        label: 'Email',
        type: 'email' as const,
        placeholder: 'Enter email',
        required: true,
    },
    {
        name: 'password',
        label: 'Password',
        type: 'password' as const,
        placeholder: props.mode === 'create' ? 'Enter password' : 'Kosongkan jika tidak ingin mengubah password',
        required: props.mode === 'create',
        help: 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka',
        showPassword: showPassword,
    },
    {
        name: 'password_confirmation',
        label: 'Konfirmasi Password',
        type: 'password' as const,
        placeholder: 'Konfirmasi password',
        required: props.mode === 'create',
        help: 'Password harus sama dengan password di atas',
        showPassword: showPasswordConfirmation,
    },
    {
        name: 'no_hp',
        label: 'No. HP',
        type: 'text' as const,
        placeholder: 'Enter phone number',
        required: true,
    },
    {
        name: 'role_id',
        label: 'Role',
        type: 'multi-select' as const,
        placeholder: 'Pilih Role (bisa lebih dari 1)',
        required: true,
        options: roleOptions,
        help: 'Pilih satu atau lebih role untuk user ini',
    },
    {
        name: 'is_active',
        label: 'Status',
        type: 'select' as const,
        placeholder: 'Pilih status',
        required: true,
        options: [
            { value: 1, label: 'Active' },
            { value: 0, label: 'Inactive' },
        ],
    },
];

const handleSave = (form: any) => {
    const formData: Record<string, any> = {
        name: form.name,
        email: form.email,
        no_hp: form.no_hp,
        role_id: form.role_id,
        is_active: form.is_active,
    };

    if (form.password) {
        formData.password = form.password;
        formData.password_confirmation = form.password_confirmation;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id;
    }

    save(formData, {
        url: '/users',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'User berhasil ditambahkan' : 'User berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan user' : 'Gagal memperbarui user',
        redirectUrl: '/users',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
