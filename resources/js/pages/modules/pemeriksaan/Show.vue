<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Detail Pemeriksaan', href: `/pemeriksaan/${props.item.id}` },
];

const fields = computed(() => {
    const status = props.item?.status;
    const statusMap = {
        belum: {
            label: 'Belum',
            class: 'text-red-800 bg-red-300',
        },
        sebagian: {
            label: 'Sebagian',
            class: 'text-yellow-800 bg-yellow-100',
        },
        selesai: {
            label: 'Selesai',
            class: 'text-green-800 bg-green-100',
        },
    };

    const statusValue = statusMap[status] || { label: '-', class: 'text-gray-500' };

    return [
        { label: 'Cabor', value: props.item?.cabor?.nama || '-' },
        { label: 'Kategori', value: props.item?.cabor_kategori?.nama || '-' },
        { label: 'Tenaga Pendukung', value: props.item?.tenaga_pendukung?.nama || '-' },
        { label: 'Nama Pemeriksaan', value: props.item?.nama_pemeriksaan || '-' },
        { label: 'Tanggal Pemeriksaan', value: props.item?.tanggal_pemeriksaan || '-' },
        {
            label: 'Status',
            value: statusValue.label,
            className: `inline-block px-2 py-1 text-xs font-semibold rounded-full ${statusValue.class}`,
        },
    ];
});

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleDelete = () => {
    router.delete(`/pemeriksaan/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data pemeriksaan berhasil dihapus', variant: 'success' });
            router.visit('/pemeriksaan');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data pemeriksaan', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Pemeriksaan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/pemeriksaan'"
        :on-edit="() => router.visit(`/pemeriksaan/${props.item.id}/edit`)"
        :on-delete="handleDelete"
    />
</template>
