<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        cabor_nama: string;
        nama: string;
        deskripsi: string;
        created_at: string;
        created_by_user: { id: number; name: string } | null;
        updated_at: string;
        updated_by_user: { id: number; name: string } | null;
    };
}>();

const dataItem = computed(() => props.item);

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Kategori', href: '/cabor-kategori' },
    { title: 'Detail Kategori', href: `/cabor-kategori/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Cabor', value: dataItem.value?.cabor_nama || '-' },
    { label: 'Nama Kategori', value: dataItem.value?.nama || '-' },
    {
        label: 'Gender',
        value: dataItem.value?.jenis_kelamin === 'L' ? 'Laki-laki' : dataItem.value?.jenis_kelamin === 'P' ? 'Perempuan' : 'Campuran',
    },
    { label: 'Deskripsi', value: dataItem.value?.deskripsi || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/cabor-kategori/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/cabor-kategori/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data kategori berhasil dihapus', variant: 'success' });
            router.visit('/cabor-kategori');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data kategori', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Kategori"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/cabor-kategori'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
