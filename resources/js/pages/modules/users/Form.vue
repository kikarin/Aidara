<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const { save } = useHandleFormSave();

const PESERTA_ROLE_IDS = [35, 36, 37] as const;
const ROLE_TO_TYPE: Record<number, string> = {
    35: 'atlet',
    36: 'pelatih',
    37: 'tenaga_pendukung',
};

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    roles: Record<number, string>;
    selectedRoles?: number[];
    pesertaInfo?: {
        peserta_type?: string;
        peserta_id?: number;
        peserta_nama?: string;
        peserta_nik?: string;
        edit_url?: string;
    } | null;
}>();

const roleOptions = Object.entries(props.roles).map(([id, name]) => ({
    value: Number(id),
    label: name,
}));

const selectedRoleIds = ref<number[]>(props.selectedRoles || []);
const pesertaOptions = ref<{ value: number; label: string }[]>([]);
const pesertaSearch = ref('');
const loadingPeserta = ref(false);
const selectedPesertaId = ref<number | ''>(
    props.pesertaInfo?.peserta_id ?? props.initialData?.peserta_id ?? '',
);

const normalizeRoleIds = (roles: unknown): number[] => {
    if (!Array.isArray(roles)) return [];
    return roles.map((id) => Number(id)).filter((id) => !Number.isNaN(id));
};

const primaryRoleId = computed(() => selectedRoleIds.value[0] ?? null);
const needsPeserta = computed(() => {
    const id = primaryRoleId.value;
    return id != null && (PESERTA_ROLE_IDS as readonly number[]).includes(id);
});
const pesertaType = computed(() =>
    primaryRoleId.value != null ? ROLE_TO_TYPE[primaryRoleId.value] ?? null : null,
);

const formData = computed(() => ({
    name: props.initialData?.name || '',
    email: props.initialData?.email || '',
    password: '',
    password_confirmation: '',
    no_hp: props.initialData?.no_hp || '',
    role_id: selectedRoleIds.value,
    is_active: props.initialData?.is_active !== undefined ? props.initialData.is_active : 1,
    peserta_id: needsPeserta.value ? selectedPesertaId.value : '',
    id: props.initialData?.id || undefined,
}));

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

function syncRoleSelection(roles: unknown) {
    selectedRoleIds.value = normalizeRoleIds(roles);
}

function handleFieldUpdated(payload: { field: string; value: unknown }) {
    if (payload.field === 'role_id') {
        syncRoleSelection(payload.value);
    }
    if (payload.field === 'peserta_id') {
        selectedPesertaId.value = payload.value != null && payload.value !== ''
            ? Number(payload.value)
            : '';
    }
}

function handleFormChange(data: Record<string, unknown>) {
    if ('role_id' in data) {
        syncRoleSelection(data.role_id);
    }
}

async function fetchPesertaOptions(search = '') {
    if (!needsPeserta.value || !pesertaType.value) {
        pesertaOptions.value = [];
        return;
    }

    loadingPeserta.value = true;
    try {
        const response = await axios.get('/api/users/peserta-options', {
            params: {
                type: pesertaType.value,
                search: search || undefined,
                user_id: props.initialData?.id || undefined,
            },
        });
        pesertaOptions.value = (response.data.data || []).map((row: { value: number; label: string }) => ({
            value: row.value,
            label: row.label,
        }));
    } catch {
        pesertaOptions.value = [];
    } finally {
        loadingPeserta.value = false;
    }
}

watch(
    () => props.selectedRoles,
    (roles) => {
        syncRoleSelection(roles);
    },
    { immediate: true },
);

watch(
    [needsPeserta, pesertaType],
    () => {
        if (needsPeserta.value) {
            fetchPesertaOptions(pesertaSearch.value);
        } else {
            selectedPesertaId.value = '';
            pesertaOptions.value = [];
        }
    },
    { immediate: true },
);

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(pesertaSearch, (value) => {
    if (!needsPeserta.value) return;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchPesertaOptions(value), 300);
});

const baseFormInputs = computed(() => [
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
        label: 'Kata Sandi',
        type: 'password' as const,
        placeholder: props.mode === 'create' ? 'Enter password' : 'Kosongkan jika tidak ingin mengubah password',
        required: props.mode === 'create',
        help: 'Kata sandi harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka',
        showPassword: showPassword,
    },
    {
        name: 'password_confirmation',
        label: 'Konfirmasi Kata Sandi',
        type: 'password' as const,
        placeholder: 'Konfirmasi password',
        required: props.mode === 'create',
        help: 'Kata sandi harus sama dengan password di atas',
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
        help: 'Role pertama (primary) menentukan jenis peserta yang wajib dipilih.',
    },
]);

const formInputs = computed(() => {
    const inputs = [...baseFormInputs.value];

    if (needsPeserta.value) {
        inputs.push({
            name: 'peserta_id',
            label: 'Data Peserta',
            type: 'select' as const,
            placeholder: loadingPeserta.value ? 'Memuat data peserta...' : 'Pilih data peserta',
            required: true,
            options: pesertaOptions.value,
            help: 'Wajib untuk role Atlet, Pelatih, atau Tenaga Pendukung. Hanya menampilkan peserta yang belum terhubung ke akun lain.',
        });
    }

    inputs.push({
        name: 'is_active',
        label: 'Status',
        type: 'select' as const,
        placeholder: 'Pilih status',
        required: true,
        options: [
            { value: 1, label: 'Aktif' },
            { value: 0, label: 'Nonaktif' },
        ],
    });

    return inputs;
});

const handleSave = (form: Record<string, any>) => {
    const payload: Record<string, any> = {
        name: form.name,
        email: form.email,
        no_hp: form.no_hp,
        role_id: form.role_id,
        is_active: form.is_active,
    };

    if (needsPeserta.value) {
        payload.peserta_id = form.peserta_id;
        payload.peserta_type = pesertaType.value;
    }

    if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
    }

    if (props.mode === 'edit' && props.initialData?.id) {
        payload.id = props.initialData.id;
    }

    save(payload, {
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
    <div class="space-y-4">
        <div
            v-if="props.mode === 'edit' && props.pesertaInfo?.peserta_id"
            class="rounded-lg border border-border bg-muted/40 p-4 text-sm"
        >
            <p class="font-medium text-foreground">Peserta terhubung saat ini</p>
            <p class="mt-1 text-muted-foreground">
                ID #{{ props.pesertaInfo.peserta_id }}
                <span v-if="props.pesertaInfo.peserta_nama"> · {{ props.pesertaInfo.peserta_nama }}</span>
                <span v-if="props.pesertaInfo.peserta_nik"> · NIK {{ props.pesertaInfo.peserta_nik }}</span>
            </p>
            <a
                v-if="props.pesertaInfo.edit_url"
                :href="props.pesertaInfo.edit_url"
                class="mt-2 inline-block text-primary underline-offset-2 hover:underline"
            >
                Buka detail peserta
            </a>
        </div>

        <div v-if="needsPeserta" class="space-y-2">
            <label class="text-sm font-medium text-foreground">Cari Peserta</label>
            <input
                v-model="pesertaSearch"
                type="text"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Ketik nama, NIK, atau email..."
            />
        </div>

        <FormInput
            :form-inputs="formInputs"
            :initial-data="formData"
            :disable-auto-reset="props.mode === 'create'"
            @save="handleSave"
            @field-updated="handleFieldUpdated"
            @update:model-value="handleFormChange"
        />
    </div>
</template>
