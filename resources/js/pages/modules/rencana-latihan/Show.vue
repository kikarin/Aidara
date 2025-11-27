<script setup lang="ts">
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ infoHeader: any; item: any }>();

const info = computed(() => props.infoHeader || {});
const dataItem = computed(() => props.item || {});

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${info.value.program_latihan_id}/rencana-latihan` },
    { title: 'Detail Rencana', href: '#' },
];

const fields = computed(() => [
    { label: 'Tanggal', value: dataItem.value.tanggal ? new Date(dataItem.value.tanggal).toLocaleDateString('id-ID') : '-' },
    { label: 'Materi', value: dataItem.value.materi || '-' },
    { label: 'Lokasi Latihan', value: dataItem.value.lokasi_latihan || '-' },
    { label: 'Catatan', value: dataItem.value.catatan || '-' },
    { label: 'Target Latihan', value: (dataItem.value.target_latihan || []).map((t: any) => t.deskripsi).join(', ') || '-' },
    { label: 'Atlet', value: (dataItem.value.atlets || []).map((a: any) => a.nama).join(', ') || '-' },
    { label: 'Pelatih', value: (dataItem.value.pelatihs || []).map((p: any) => p.nama).join(', ') || '-' },
    { label: 'Tenaga Pendukung', value: (dataItem.value.tenaga_pendukung || []).map((t: any) => t.nama).join(', ') || '-' },
]);

const actionFields = computed(() => [
    {
        label: 'Created At',
        value: dataItem.value.created_at ? new Date(dataItem.value.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
    },
    { label: 'Created By', value: dataItem.value.created_by_user?.name || '-' },
    {
        label: 'Updated At',
        value: dataItem.value.updated_at ? new Date(dataItem.value.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
    },
    { label: 'Updated By', value: dataItem.value.updated_by_user?.name || '-' },
]);

const handleEdit = () => {
    router.visit(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${dataItem.value.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan/${dataItem.value.id}`, {
        onSuccess: () => {
            router.visit(`/program-latihan/${info.value.program_latihan_id}/rencana-latihan`);
        },
    });
};
</script>

<template>
    <PageShow
        title="Rencana Latihan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/program-latihan/${info.program_latihan_id}/rencana-latihan`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
