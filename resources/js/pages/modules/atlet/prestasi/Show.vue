<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    atletId: number;
    item: {
        id: number;
        nama_event: string;
        tingkat_id?: number;
        tingkat?: { id: number; nama: string };
        tanggal?: string;
        juara?: string;
        medali?: string;
        jenis_prestasi?: string;
        kategori_peserta?: { id: number; nama: string };
        keterangan?: string;
        bonus?: number;
        created_at: string;
        created_by_user?: { name: string } | null;
        updated_at: string;
        updated_by_user?: { name: string } | null;
    };
}>();

const breadcrumbs = [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Prestasi', href: `/atlet/${props.atletId}/prestasi` },
    { title: 'Detail Prestasi', href: `/atlet/${props.atletId}/prestasi/${props.item.id}` },
];

const formatRupiah = (value: number | null | undefined): string => {
    if (!value || value === 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const getJenisPrestasiLabel = (jenis: string | undefined) => {
    if (!jenis) return '-';
    return jenis === 'individu' ? 'Individu' : 'Ganda/Mixed/Beregu/Double';
};

const fields = computed(() => [
    { label: 'Nama Event', value: props.item?.nama_event || '-' },
    { label: 'Kategori Peserta', value: props.item?.kategori_peserta?.nama || '-' },
    { label: 'Jenis Prestasi', value: getJenisPrestasiLabel(props.item?.jenis_prestasi) },
    { label: 'Tingkat', value: props.item?.tingkat?.nama || '-' },
    {
        label: 'Tanggal',
        value: props.item?.tanggal
            ? new Date(props.item.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
            : '-',
    },
    { label: 'Juara', value: props.item?.juara || '-' },
    { label: 'Medali', value: props.item?.medali || '-' },
    { label: 'Bonus', value: formatRupiah(props.item?.bonus || 0) },
    { label: 'Keterangan', value: props.item?.keterangan || '-', className: 'sm:col-span-2' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/atlet/${props.atletId}/prestasi/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/atlet/${props.atletId}/prestasi/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Prestasi berhasil dihapus', variant: 'success' });
            router.visit(`/atlet/${props.atletId}/prestasi`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus prestasi', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Prestasi"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/atlet/${props.atletId}/prestasi`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
    </PageShow>
</template>
