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
        jenis_unit_pendukung_id: number;
        jenis_unit_pendukung: {
            id: number;
            nama: string;
        } | null;
        deskripsi: string;
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
    };
}>();

const dataItem = computed(() => props.item);

const breadcrumbs = [
    { title: 'Unit Pendukung', href: '/unit-pendukung' },
    { title: 'Detail Unit Pendukung', href: `/unit-pendukung/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Nama Unit Pendukung', value: dataItem.value?.nama || '-' },
    { label: 'Jenis Unit Pendukung', value: dataItem.value?.jenis_unit_pendukung?.nama || '-' },
    { label: 'Deskripsi', value: dataItem.value?.deskripsi || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/unit-pendukung/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/unit-pendukung/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data unit pendukung berhasil dihapus', variant: 'success' });
            router.visit('/unit-pendukung');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data unit pendukung', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Unit Pendukung"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/unit-pendukung'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
