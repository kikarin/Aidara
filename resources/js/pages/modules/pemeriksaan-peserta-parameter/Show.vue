<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();
const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const peserta = computed(() => page.props.peserta || {});
const item = computed(() => page.props.item || {});
const jenisPeserta = computed(() => {
    return (
        page.props.jenis_peserta ||
        (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('jenis_peserta') || 'atlet' : 'atlet')
    );
});

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${pemeriksaan.value.id}/peserta?jenis_peserta=${jenisPeserta.value}` },
    {
        title: 'Parameter Peserta',
        href: `/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter?jenis_peserta=${jenisPeserta.value}`,
    },
    {
        title: 'Detail Parameter Peserta',
        href: `/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter/${item.value.id}?jenis_peserta=${jenisPeserta.value}`,
    },
];

const fields = computed(() => [
    {
        label: 'Parameter',
        value: typeof item.value?.parameter === 'string' ? item.value.parameter : item.value?.parameter?.nama_parameter || '-',
    },
    { label: 'Nilai', value: item.value?.nilai ?? '-' },
    { label: 'Trend', value: item.value?.trend || '-' },
    { label: 'Pemeriksaan', value: pemeriksaan.value?.nama_pemeriksaan || '-' },
    {
        label: 'Peserta',
        value: typeof item.value?.peserta === 'string' ? item.value.peserta : peserta.value?.peserta?.nama || peserta.value?.peserta || '-',
    },
]);

const actionFields = [
    {
        label: 'Created At',
        value: item.value.created_at ? new Date(item.value.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
    },
    {
        label: 'Created By',
        value: typeof item.value.created_by_user === 'object' ? item.value.created_by_user?.name || '-' : item.value.created_by_user || '-',
    },
    {
        label: 'Updated At',
        value: item.value.updated_at ? new Date(item.value.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
    },
    {
        label: 'Updated By',
        value: typeof item.value.updated_by_user === 'object' ? item.value.updated_by_user?.name || '-' : item.value.updated_by_user || '-',
    },
];

const handleDelete = () => {
    router.delete(`/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter/${item.value.id}?jenis_peserta=${jenisPeserta.value}`, {
        onSuccess: () => {
            toast({ title: 'Data parameter peserta berhasil dihapus', variant: 'success' });
            router.visit(`/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter?jenis_peserta=${jenisPeserta.value}`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data parameter peserta', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Parameter Peserta"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter?jenis_peserta=${jenisPeserta}`"
        :on-edit="() => router.visit(`/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter/${item.id}/edit?jenis_peserta=${jenisPeserta}`)"
        :on-delete="handleDelete"
    />
</template>
