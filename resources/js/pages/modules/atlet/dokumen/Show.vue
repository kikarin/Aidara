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
        jenis_dokumen_id?: number;
        nomor?: string;
        file_url?: string;
        created_at: string;
        created_by_user?: { name: string } | null;
        updated_at: string;
        updated_by_user?: { name: string } | null;
    };
}>();

const breadcrumbs = [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Dokumen', href: `/atlet/${props.atletId}/dokumen` },
    { title: 'Detail Dokumen', href: `/atlet/${props.atletId}/dokumen/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Jenis Dokumen', value: props.item?.jenis_dokumen?.nama || '-' },
    { label: 'Nomor Dokumen', value: props.item?.nomor || '-' },
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
    router.visit(`/atlet/${props.atletId}/dokumen/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/atlet/${props.atletId}/dokumen/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Dokumen berhasil dihapus', variant: 'success' });
            router.visit(`/atlet/${props.atletId}/dokumen`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus dokumen', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Dokumen"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/atlet/${props.atletId}/dokumen`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
    </PageShow>
</template>
