<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        name: string;
        email: string;
        no_hp: string;
        role: {
            id: number;
            name: string;
            init_page_login: string;
            is_allow_login: number;
            bg: string;
            is_vertical_menu: number;
        };
        is_active: number;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
        all_roles: string;
        peserta_info?: {
            peserta_type?: string;
            peserta_type_label?: string;
            peserta_id?: number;
            peserta_nama?: string;
            peserta_nik?: string;
            peserta_is_active?: boolean | null;
            edit_url?: string | null;
            registration_status?: string | null;
            is_orphan?: boolean;
        } | null;
    };
}>();

const user = computed(() => props.item);

const breadcrumbs = [
    { title: 'Users', href: '/users' },
    { title: 'Detail User', href: `/users/${props.item.id}` },
];

const fields = computed(() => {
    // Pastikan all_roles selalu string
    let allRoles = user.value?.all_roles;
    if (Array.isArray(allRoles)) {
        allRoles = allRoles.join(', ');
    }
    return [
        { label: 'Name', value: user.value?.name || '-' },
        { label: 'Email', value: user.value?.email || '-' },
        { label: 'No. HP', value: user.value?.no_hp || '-' },
        { label: 'Role', value: user.value?.role?.name || '-' },
        {
            label: 'Semua Peran',
            value:
                allRoles && allRoles !== ''
                    ? `<div class='flex flex-wrap'>${allRoles
                          .split(', ')
                          .map(
                              (role: string) =>
                                  `<span class='badge-role inline-flex items-center px-2 py-1 text-xs font-medium rounded-full mr-1 mb-1'>${role.trim()}</span>`,
                          )
                          .join('')}</div>`
                    : '-',
        },
        {
            label: 'Status',
            value: user.value?.is_active ? 'Aktif' : 'Nonaktif',
            className: user.value?.is_active ? 'text-green-600' : 'text-red-600',
        },
        {
            label: 'Jenis Peserta',
            value: user.value?.peserta_info?.peserta_type_label || '-',
        },
        {
            label: 'ID Peserta',
            value: user.value?.peserta_info?.peserta_id
                ? `#${user.value.peserta_info.peserta_id}`
                : '-',
        },
        {
            label: 'Nama Peserta',
            value: user.value?.peserta_info?.peserta_nama || '-',
        },
        {
            label: 'NIK Peserta',
            value: user.value?.peserta_info?.peserta_nik || '-',
        },
        {
            label: 'Status Peserta',
            value:
                user.value?.peserta_info?.peserta_is_active === true
                    ? 'Aktif'
                    : user.value?.peserta_info?.peserta_is_active === false
                      ? 'Nonaktif'
                      : '-',
        },
        {
            label: 'Status Registrasi',
            value: user.value?.peserta_info?.registration_status || '-',
        },
        {
            label: 'Detail Peserta',
            value: user.value?.peserta_info?.edit_url
                ? `<a href="${user.value.peserta_info.edit_url}" class="text-primary underline-offset-2 hover:underline">Buka profil peserta</a>`
                : '-',
        },
    ];
});

const actionFields = [
    { label: 'Dibuat Pada', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Dibuat Oleh', value: props.item.created_by_user?.name || '-' },
    { label: 'Diperbarui Pada', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Diperbarui Oleh', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/users/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/users/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'User berhasil dihapus', variant: 'success' });
            router.visit('/users');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus user', variant: 'destructive' });
        },
    });
};

// Debug untuk melihat data
console.log('Show data:', props.item);
</script>

<template>
    <PageShow
        title="Users"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/users'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
