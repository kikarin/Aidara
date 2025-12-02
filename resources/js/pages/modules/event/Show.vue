<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama_event: string;
        deskripsi: string | null;
        foto: string | null;
        kategori_event_id: number | null;
        tingkat_event_id: number | null;
        lokasi: string | null;
        tanggal_mulai: string | null;
        tanggal_selesai: string | null;
        status: string;
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
        kategori_event?: {
            id: number;
            nama: string;
            cabor?: {
                id: number;
                nama: string;
            };
        } | null;
        tingkat_event?: {
            id: number;
            nama: string;
        } | null;
    };
}>();

const dataItem = computed(() => props.item);

const breadcrumbs = [
    { title: 'Event', href: '/event' },
    { title: 'Detail Event', href: `/event/${props.item.id}` },
];

const statusMap: Record<string, { label: string; class: string }> = {
    draft: { label: 'Draft', class: 'bg-gray-500' },
    publish: { label: 'Publish', class: 'bg-blue-500' },
    selesai: { label: 'Selesai', class: 'bg-green-500' },
    dibatalkan: { label: 'Dibatalkan', class: 'bg-red-500' },
};

const fields = computed(() => {
    const baseFields = [
        { label: 'Nama Event', value: dataItem.value?.nama_event || '-' },
        {
            label: 'Deskripsi',
            value: dataItem.value?.deskripsi || '-',
        },
        {
            label: 'Foto Event',
            value: dataItem.value?.foto ? `/storage/${dataItem.value.foto}` : '',
            type: 'image' as const,
            imageConfig: {
                size: 'md' as const,
                labelText: 'Klik untuk melihat lebih besar',
            },
        },
        {
            label: 'Kategori Event',
            value: dataItem.value?.kategori_event
                ? `${dataItem.value.kategori_event.cabor?.nama || ''} - ${dataItem.value.kategori_event.nama || ''}`
                : '-',
        },
        {
            label: 'Tingkat Event',
            value: dataItem.value?.tingkat_event?.nama || '-',
        },
        { label: 'Lokasi', value: dataItem.value?.lokasi || '-' },
        {
            label: 'Tanggal Mulai',
            value: dataItem.value?.tanggal_mulai
                ? new Date(dataItem.value.tanggal_mulai).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                  })
                : '-',
        },
        {
            label: 'Tanggal Selesai',
            value: dataItem.value?.tanggal_selesai
                ? new Date(dataItem.value.tanggal_selesai).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'long',
                      year: 'numeric',
                  })
                : '-',
        },
        {
            label: 'Status',
            value: statusMap[dataItem.value?.status || '']?.label || dataItem.value?.status || '-',
        },
    ];

    return baseFields;
});

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/event/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/event/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data event berhasil dihapus', variant: 'success' });
            router.visit('/event');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data event', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Event"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/event'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>

