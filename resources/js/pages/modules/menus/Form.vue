<script setup lang="ts">
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { computed, ref } from 'vue';

const { save } = useHandleFormSave();

// Icon yang sering digunakan
const COMMON_ICONS = [
    { value: '-', label: 'Tidak ada icon' },
    { value: 'LayoutGrid', label: 'Layout Grid' },
    { value: 'Users', label: 'Users' },
    { value: 'Settings', label: 'Settings' },
    { value: 'FileText', label: 'File Text' },
    { value: 'Folder', label: 'Folder' },
    { value: 'Shield', label: 'Shield' },
    { value: 'User', label: 'User' },
    { value: 'List', label: 'List' },
    { value: 'Plus', label: 'Plus' },
    { value: 'Edit', label: 'Edit' },
    { value: 'Trash', label: 'Trash' },
    { value: 'Search', label: 'Search' },
    { value: 'Filter', label: 'Filter' },
    { value: 'Download', label: 'Download' },
    { value: 'Upload', label: 'Upload' },
    { value: 'Menu', label: 'Menu' },
    { value: 'Home', label: 'Home' },
    { value: 'BarChart', label: 'Bar Chart' },
    { value: 'PieChart', label: 'Pie Chart' },
    { value: 'Calendar', label: 'Calendar' },
];

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
    listUsersMenu: Record<number, string>;
    get_Permission: Record<number, string>;
}>();

// Inisialisasi formData dengan data awal jika mode edit
const formData = ref<Record<string, any>>({
    nama: props.initialData?.nama || '',
    kode: props.initialData?.kode || '',
    icon: props.initialData?.icon || '',
    rel: props.initialData?.rel || 0,
    permission_id: props.initialData?.permission_id || '',
    url: props.initialData?.url || '',
    urutan: props.initialData?.urutan || 1,
    id: props.initialData?.id || undefined,
});

const parentOptions = computed(() => {
    return Object.entries(props.listUsersMenu).map(([id, name]) => ({
        value: Number(id),
        label: name,
    }));
});

const permissionOptions = Object.entries(props.get_Permission).map(([id, name]) => ({
    value: Number(id),
    label: name,
}));

const formInputs = [
    {
        name: 'nama',
        label: 'Name',
        type: 'text' as const,
        placeholder: 'Menu name',
        required: true,
        rules: [
            { required: true, message: 'Nama menu harus diisi' },
            { max: 200, message: 'Nama menu maksimal 200 karakter' },
        ],
    },
    {
        name: 'kode',
        label: 'Code',
        type: 'text' as const,
        placeholder: 'Unique code',
        required: true,
        rules: [
            { required: true, message: 'Kode menu harus diisi' },
            { max: 200, message: 'Kode menu maksimal 200 karakter' },
        ],
    },
    {
        name: 'icon',
        label: 'Icon',
        type: 'select' as const,
        placeholder: 'Pilih icon',
        required: false,
        options: COMMON_ICONS,
        help: 'Pilih icon dari daftar yang tersedia atau biarkan kosong jika tidak memerlukan icon.',
        suffix: {
            type: 'link',
            text: 'Lihat Semua Icon',
            href: 'https://lucide.dev/icons/',
            target: '_blank',
        },
    },
    {
        name: 'rel',
        label: 'Parent Menu',
        type: 'select' as const,
        placeholder: 'Choose parent',
        required: false,
        options: parentOptions.value,
        help: 'Pilih menu parent. Jika ini adalah menu utama, pilih "Menu Utama".',
        defaultValue: 0,
    },
    {
        name: 'permission_id',
        label: 'Permission',
        type: 'select' as const,
        placeholder: 'Choose permission',
        required: true,
        options: permissionOptions,
        rules: [{ required: true, message: 'Permission harus dipilih' }],
    },
    {
        name: 'url',
        label: 'URL',
        type: 'text' as const,
        placeholder: '/example/path',
        required: true,
        rules: [{ required: true, message: 'URL harus diisi' }],
    },
    {
        name: 'urutan',
        label: 'Order',
        type: 'number' as const,
        placeholder: '1, 2, 3...',
        required: true,
        rules: [
            { required: true, message: 'Urutan harus diisi' },
            { type: 'number', min: 1, message: 'Urutan minimal 1' },
        ],
    },
];

const handleSave = (data: Record<string, any>) => {
    const formData: Record<string, any> = {
        ...data,
        rel: data.rel === '' ? 0 : Number(data.rel),
        permission_id: Number(data.permission_id),
        urutan: Number(data.urutan),
        icon: data.icon || null,
    };

    if (props.mode === 'edit' && props.initialData?.id) {
        formData.id = props.initialData.id; // dibutuhkan untuk validasi update
    }

    save(formData, {
        url: '/menu-permissions/menus',
        mode: props.mode,
        id: props.initialData?.id,
        redirectUrl: '/menu-permissions/menus',
        successMessage: props.mode === 'create' ? 'Menu berhasil ditambahkan' : 'Menu berhasil diperbarui',
        errorMessage: 'Gagal menyimpan menu',
    });
};
</script>

<template>
    <FormInput :form-inputs="formInputs" :initial-data="formData" @save="handleSave" />
</template>
