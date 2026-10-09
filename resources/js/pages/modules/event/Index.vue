<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Event', href: '/event' },
];

const columns = [
    { key: 'nama_event', label: 'Nama Event' },
    {
        key: 'foto',
        label: 'Foto',
        orderable: false,
        format: (row: any) => {
            if (row.foto_url) {
                return `<div class="cursor-pointer" onclick="window.open('${row.foto_url}', '_blank')">
                    <img src="${row.foto_url}" alt="Foto ${row.nama_event}" class="h-16 w-16 rounded object-cover border hover:shadow-md transition-shadow" />
                </div>`;
            }
            return '<div class="flex h-16 w-16 items-center justify-center rounded bg-muted text-xs text-muted-foreground">-</div>';
        },
    },
    { key: 'kategori_event_nama', label: 'Kategori Event', orderable: false },
    { key: 'tingkat_event_nama', label: 'Tingkat Event', orderable: false },
    {
        key: 'tanggal_mulai',
        label: 'Tanggal Mulai',
        format: (row: any) => {
            if (!row.tanggal_mulai) return '-';
            const date = new Date(row.tanggal_mulai);
            const options: Intl.DateTimeFormatOptions = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
            return date.toLocaleDateString('id-ID', options);
        },
    },
    {
        key: 'tanggal_selesai',
        label: 'Tanggal Selesai',
        format: (row: any) => {
            if (!row.tanggal_selesai) return '-';
            const date = new Date(row.tanggal_selesai);
            const options: Intl.DateTimeFormatOptions = {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            };
            return date.toLocaleDateString('id-ID', options);
        },
    },
    { key: 'lokasi', label: 'Lokasi' },
    {
        key: 'status',
        label: 'Status',
        format: (row: any) => {
            const statusMap: Record<string, { label: string; class: string }> = {
                draft: { label: 'Draft', class: 'bg-muted-foreground' },
                publish: { label: 'Publish', class: 'bg-blue-500' },
                selesai: { label: 'Selesai', class: 'bg-green-500' },
                dibatalkan: { label: 'Dibatalkan', class: 'bg-red-500' },
            };
            const status = statusMap[row.status] || { label: row.status, class: 'bg-muted-foreground' };
            return `<span class="px-2 py-1 rounded text-white text-xs ${status.class}">${status.label}</span>`;
        },
    },
];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/event/${row.id}`),
        permission: 'Event Detail',
    },
    {
        label: 'Ubah',
        onClick: () => router.visit(`/event/${row.id}/edit`),
        permission: 'Event Edit',
    },
    {
        label: 'Hapus',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Event Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/event/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message,
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);

        const message = error.response?.data?.message;
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const deleteRow = async (row: any) => {
    await router.delete(`/event/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data event berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Event"
            module-name="Event"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/event/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/event"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
        />
    </div>
</template>

