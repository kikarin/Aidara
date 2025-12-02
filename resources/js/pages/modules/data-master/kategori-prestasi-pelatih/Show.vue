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
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Prestasi Pelatih', href: '/data-master/kategori-prestasi-pelatih' },
    { title: 'Detail Kategori Prestasi Pelatih', href: `/data-master/kategori-prestasi-pelatih/${props.item.id}` },
];

const fields = computed(() => [{ label: 'Nama Kategori Prestasi Pelatih', value: dataItem.value?.nama || '-' }]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/data-master/kategori-prestasi-pelatih/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/data-master/kategori-prestasi-pelatih/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data kategori prestasi pelatih berhasil dihapus', variant: 'success' });
            router.visit('/data-master/kategori-prestasi-pelatih');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data kategori prestasi pelatih', variant: 'destructive' });
        },
    });
};

console.log('Show data:', props.item);
</script>

<template>
    <PageShow
        title="Kategori Prestasi Pelatih"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/data-master/kategori-prestasi-pelatih'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
