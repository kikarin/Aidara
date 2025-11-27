<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const pemeriksaanId = computed(() => pemeriksaan.value.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));

const props = defineProps<{ item: Record<string, any> }>();

const jenisPeserta = computed(() => {
    const pesertaType = props.item?.peserta_type || '';
    if (pesertaType.includes('Atlet')) return 'atlet';
    if (pesertaType.includes('Pelatih')) return 'pelatih';
    if (pesertaType.includes('TenagaPendukung')) return 'tenaga-pendukung';
    return 'atlet';
});

const pesertaLabel = computed(() => {
    switch (jenisPeserta.value) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga-pendukung':
            return 'Tenaga Pendukung';
        default:
            return 'Peserta';
    }
});

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${pemeriksaanId.value}/peserta?jenis_peserta=${jenisPeserta.value}` },
    { title: `Detail ${pesertaLabel.value}`, href: `/pemeriksaan/${pemeriksaanId.value}/peserta/${props.item.id}` },
];

const fields = computed(() => {
    const baseFields = [
        { label: 'Status Pemeriksaan', value: props.item?.status?.nama || '-' },
        { label: 'Catatan Umum', value: props.item?.catatan_umum || '-', className: 'sm:col-span-2' },
    ];

    const pesertaFields = [];
    const peserta = props.item?.peserta || {};

    if (jenisPeserta.value === 'atlet') {
        pesertaFields.push(
            { label: 'NIK', value: peserta?.nik || '-' },
            { label: 'Nama', value: peserta?.nama || '-' },
            {
                label: 'Jenis Kelamin',
                value: peserta?.jenis_kelamin === 'L' ? 'Laki-laki' : peserta?.jenis_kelamin === 'P' ? 'Perempuan' : '-',
                className: peserta?.jenis_kelamin === 'L' ? 'text-indigo-300' : peserta?.jenis_kelamin === 'P' ? 'text-pink-600' : '',
            },
            { label: 'Tempat Lahir', value: peserta?.tempat_lahir || '-' },
            {
                label: 'Tanggal Lahir',
                value: peserta?.tanggal_lahir
                    ? new Date(peserta.tanggal_lahir).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'numeric',
                          year: 'numeric',
                      })
                    : '-',
            },
            { label: 'No HP', value: peserta?.no_hp || '-' },
            { label: 'Email', value: peserta?.email || '-' },
            {
                label: 'Foto',
                value: peserta?.foto || '',
                type: 'image' as const,
                className: 'sm:col-span-2',
                imageConfig: {
                    size: 'md' as const,
                },
            },
        );
    } else if (jenisPeserta.value === 'pelatih') {
        pesertaFields.push(
            { label: 'NIK', value: peserta?.nik || '-' },
            { label: 'Nama', value: peserta?.nama || '-' },
            {
                label: 'Jenis Kelamin',
                value: peserta?.jenis_kelamin === 'L' ? 'Laki-laki' : peserta?.jenis_kelamin === 'P' ? 'Perempuan' : '-',
                className: peserta?.jenis_kelamin === 'L' ? 'text-indigo-300' : peserta?.jenis_kelamin === 'P' ? 'text-pink-600' : '',
            },
            { label: 'Jenis Pelatih', value: peserta?.jenis_pelatih?.nama || '-' },
            { label: 'Tempat Lahir', value: peserta?.tempat_lahir || '-' },
            {
                label: 'Tanggal Lahir',
                value: peserta?.tanggal_lahir
                    ? new Date(peserta.tanggal_lahir).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'numeric',
                          year: 'numeric',
                      })
                    : '-',
            },
            { label: 'No HP', value: peserta?.no_hp || '-' },
            { label: 'Email', value: peserta?.email || '-' },
            {
                label: 'Foto',
                value: peserta?.foto || '',
                type: 'image' as const,
                className: 'sm:col-span-2',
                imageConfig: {
                    size: 'md' as const,
                },
            },
        );
    } else if (jenisPeserta.value === 'tenaga-pendukung') {
        pesertaFields.push(
            { label: 'NIK', value: peserta?.nik || '-' },
            { label: 'Nama', value: peserta?.nama || '-' },
            {
                label: 'Jenis Kelamin',
                value: peserta?.jenis_kelamin === 'L' ? 'Laki-laki' : peserta?.jenis_kelamin === 'P' ? 'Perempuan' : '-',
                className: peserta?.jenis_kelamin === 'L' ? 'text-indigo-300' : peserta?.jenis_kelamin === 'P' ? 'text-pink-600' : '',
            },
            { label: 'Jenis Tenaga Pendukung', value: peserta?.jenis_tenaga_pendukung?.nama || '-' },
            { label: 'Tempat Lahir', value: peserta?.tempat_lahir || '-' },
            {
                label: 'Tanggal Lahir',
                value: peserta?.tanggal_lahir
                    ? new Date(peserta.tanggal_lahir).toLocaleDateString('id-ID', {
                          day: 'numeric',
                          month: 'numeric',
                          year: 'numeric',
                      })
                    : '-',
            },
            { label: 'No HP', value: peserta?.no_hp || '-' },
            { label: 'Email', value: peserta?.email || '-' },
            {
                label: 'Foto',
                value: peserta?.foto || '',
                type: 'image' as const,
                className: 'sm:col-span-2',
                imageConfig: {
                    size: 'md' as const,
                },
            },
        );
    }

    // Informasi pemeriksaan
    const pemeriksaanFields = [
        { label: 'Nama Pemeriksaan', value: pemeriksaan.value?.nama_pemeriksaan || '-' },
        { label: 'Cabang Olahraga', value: pemeriksaan.value?.cabor?.nama || '-' },
        { label: 'Kategori Cabor', value: pemeriksaan.value?.cabor_kategori?.nama || '-' },
        { label: 'Tenaga Pendukung', value: pemeriksaan.value?.tenaga_pendukung?.nama || '-' },
    ];

    return [...pemeriksaanFields, ...baseFields, ...pesertaFields];
});

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleDelete = () => {
    router.delete(`/pemeriksaan/${pemeriksaanId.value}/peserta/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data peserta pemeriksaan berhasil dihapus', variant: 'success' });

            // Redirect ke URL dinamis dengan query jenis_peserta
            router.visit(`/pemeriksaan/${pemeriksaanId.value}/peserta?jenis_peserta=${jenisPeserta.value}`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data peserta pemeriksaan', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        :title="`${pesertaLabel}`"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/pemeriksaan/${pemeriksaanId}/peserta?jenis_peserta=${jenisPeserta}`"
        :on-edit="() => router.visit(`/pemeriksaan/${page.props.pemeriksaan.id}/peserta/${props.item.id}/edit?jenis_peserta=${jenisPeserta}`)"
        :on-delete="handleDelete"
    />
</template>
