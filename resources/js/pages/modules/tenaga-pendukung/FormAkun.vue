<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, ref } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const formData = ref({
    users_id: props.initialData?.user?.id || null,
    akun_email: props.initialData?.user?.email || '',
    akun_password: '',
    nama: props.initialData?.nama || '',
    no_hp: props.initialData?.no_hp || '',
});

const hasAccount = computed(() => {
    return !!props.initialData?.user?.id;
});

const showPassword = ref(false);

const formInputs = computed(() => [
    {
        name: 'akun_email',
        label: 'Email',
        type: 'email' as const,
        placeholder: 'Masukkan email untuk akun',
        required: true,
    },
    {
        name: 'akun_password',
        label: hasAccount.value ? 'Password Baru (Opsional)' : 'Password',
        type: 'password' as const,
        required: !hasAccount.value,
        help: hasAccount.value
            ? 'Kosongkan jika tidak ingin mengubah password'
            : 'Password minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, dan angka',
        showPassword: showPassword,
    },
]);

const handleSave = async (dataFromFormInput: any, setFormErrors: (errors: Record<string, string>) => void) => {
    const formFields = { ...formData.value, ...dataFromFormInput };

    const url = `/tenaga-pendukung/${props.initialData?.id}/akun`;
    const method = hasAccount.value ? 'put' : 'post';

    console.log('TenagaPendukung/FormAkun.vue: Form fields to send:', formFields);
    console.log('TenagaPendukung/FormAkun.vue: Determined URL:', url);

    try {
        const response = await axios[method](url, formFields);

        if (response.data.success) {
            toast({
                title: hasAccount.value ? 'Akun tenaga pendukung berhasil diperbarui!' : 'Akun tenaga pendukung berhasil dibuat!',
                variant: 'success',
            });

            window.location.reload();
        } else {
            toast({
                title: response.data.message || 'Terjadi kesalahan',
                variant: 'destructive',
            });
        }
    } catch (error: any) {
        console.error('Error saving akun:', error);

        if (error.response?.data?.errors) {
            setFormErrors(error.response.data.errors);
        } else {
            toast({
                title: error.response?.data?.message || 'Gagal menyimpan akun tenaga pendukung',
                variant: 'destructive',
            });
        }
    }
};
</script>

<template>
    <div>
        <div v-if="hasAccount" class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Akun sudah dibuat</h3>
                    <div class="text-sm text-green-700">
                        <p>Anda dapat mengubah email atau password di bawah ini.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Buat Akun untuk Tenaga Pendukung</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Tenaga Pendukung ini belum memiliki akun. Buat akun dengan mengisi form di bawah ini.</p>
                        <p class="mt-1"><strong>Nama:</strong> {{ initialData?.nama }} | <strong>No HP:</strong> {{ initialData?.no_hp }}</p>
                    </div>
                </div>
            </div>
        </div>

        <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
    </div>
</template>
