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

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Pemeriksaan Parameter', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter` },
    { title: 'Detail Pemeriksaan Parameter', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${props.item.id}` },
];

const fields = computed(() => [
    {
        label: 'Parameter',
        value: props.item?.mst_parameter ? `${props.item.mst_parameter.nama} (${props.item.mst_parameter.satuan})` : '-',
    },
    { label: 'Pemeriksaan', value: props.item?.pemeriksaan?.nama_pemeriksaan || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleDelete = () => {
    router.delete(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data pemerikssaan parameter berhasil dihapus', variant: 'success' });
            router.visit(`/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data pemeriksaan parameter', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Pemeriksaan Parameter"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter`"
        :on-edit="() => router.visit(`/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter/${props.item.id}/edit`)"
        :on-delete="handleDelete"
    />
</template>
