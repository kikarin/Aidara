<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    tenagaPendukungId: number;
    item: {
        id: number;
        nama_event: string;
        tingkat_id?: number;
        tanggal?: string;
        peringkat?: string;
        keterangan?: string;
        created_at: string;
        created_by_user?: { name: string } | null;
        updated_at: string;
        updated_by_user?: { name: string } | null;
    };
}>();

const breadcrumbs = [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Prestasi', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi` },
    { title: 'Detail Prestasi', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Nama Event', value: props.item?.nama_event || '-' },
    { label: 'Tingkat', value: props.item?.tingkat?.nama || '-' },
    {
        label: 'Tanggal',
        value: props.item?.tanggal
            ? new Date(props.item.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
            : '-',
    },
    { label: 'Peringkat', value: props.item?.peringkat || '-' },
    { label: 'Keterangan', value: props.item?.keterangan || '-', className: 'sm:col-span-2' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Prestasi berhasil dihapus', variant: 'success' });
            router.visit(`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`);
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
        :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
    </PageShow>
</template>
