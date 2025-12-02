<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        deskripsi: string;
        created_at: string;
        created_by_user: { id: number; name: string } | null;
        updated_at: string;
        updated_by_user: { id: number; name: string } | null;
    };
}>();

const dataItem = computed(() => props.item);

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Detail Cabor', href: `/cabor/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Nama Cabor', value: dataItem.value?.nama || '-' },
    { label: 'Deskripsi', value: dataItem.value?.deskripsi || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/cabor/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/cabor/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data cabor berhasil dihapus', variant: 'success' });
            router.visit('/cabor');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data cabor', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Cabor"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/cabor'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
