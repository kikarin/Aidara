<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{ item: any }>();

const dataItem = computed(() => props.item);

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Detail Program Latihan', href: `/program-latihan/${props.item.id}` },
];

const formatJenisPeriode = (jenis: string) => {
    if (!jenis) return '-';
    const mapping: Record<string, string> = {
        harian: 'Harian',
        mingguan: 'Mingguan',
        bulanan: 'Bulanan',
        tahunan: 'Tahunan',
    };
    return mapping[jenis] || jenis;
};

const fields = computed(() => [
    { label: 'Nama Program', value: dataItem.value?.nama_program || '-' },
    { label: 'Cabor', value: dataItem.value?.cabor?.nama || '-' },
    { label: 'Kategori', value: dataItem.value?.cabor_kategori?.nama || '-' },
    {
        label: 'Periode',
        value:
            dataItem.value?.periode_mulai && dataItem.value?.periode_selesai
                ? `${dataItem.value.periode_mulai} s/d ${dataItem.value.periode_selesai}`
                : '-',
    },
    {
        label: 'Jenis Periode',
        value: formatJenisPeriode(dataItem.value?.jenis_periode),
    },
    ...(dataItem.value?.file_url
        ? [
              {
                  label: 'File Program',
                  value: dataItem.value.file_url,
                  type: 'file' as const,
              },
          ]
        : [
              {
                  label: 'File Program',
                  value: '-',
              },
          ]),
    { label: 'Keterangan', value: dataItem.value?.keterangan || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/program-latihan/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/program-latihan/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data program latihan berhasil dihapus', variant: 'success' });
            router.visit('/program-latihan');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data program latihan', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Program Latihan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/program-latihan'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
