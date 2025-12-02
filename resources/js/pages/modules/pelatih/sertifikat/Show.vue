<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    pelatihId: number;
    item: {
        id: number;
        nama_sertifikat: string;
        penyelenggara?: string;
        tanggal_terbit?: string;
        file_url?: string;
        created_at: string;
        created_by_user?: { name: string } | null;
        updated_at: string;
        updated_by_user?: { name: string } | null;
    };
}>();

const breadcrumbs = [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Sertifikat', href: `/pelatih/${props.pelatihId}/sertifikat` },
    { title: 'Detail Sertifikat', href: `/pelatih/${props.pelatihId}/sertifikat/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Nama Sertifikat', value: props.item?.nama_sertifikat || '-' },
    { label: 'Penyelenggara', value: props.item?.penyelenggara || '-' },
    {
        label: 'Tanggal Terbit',
        value: props.item?.tanggal_terbit
            ? new Date(props.item.tanggal_terbit).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' })
            : '-',
    },
    props.item?.file_url
        ? {
              label: 'File',
              value: props.item.file_url,
              type: 'file' as const,
          }
        : {
              label: 'File',
              value: '-',
              type: 'file' as const,
          },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/pelatih/${props.pelatihId}/sertifikat/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/pelatih/${props.pelatihId}/sertifikat/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Sertifikat berhasil dihapus', variant: 'success' });
            router.visit(`/pelatih/${props.pelatihId}/sertifikat`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus sertifikat', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Sertifikat"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/pelatih/${props.pelatihId}/sertifikat`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
    </PageShow>
</template>
