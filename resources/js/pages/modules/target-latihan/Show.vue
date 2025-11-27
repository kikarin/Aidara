<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{ item: any }>();

const dataItem = computed(() => props.item);

const programId = computed(
    () =>
        props.item?.program_latihan_id ||
        props.item?.program_latihan?.id ||
        (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''),
);
const jenisTarget = computed(() => props.item?.jenis_target || (typeof window !== 'undefined' ? window.location.pathname.split('/')[4] : ''));

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}` },
    { title: 'Detail Target', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${props.item.id}` },
];

const fields = computed(() => {
    const baseFields = [
        { label: 'Deskripsi Target', value: dataItem.value?.deskripsi || '-' },
        { label: 'Satuan', value: dataItem.value?.satuan || '-' },
        { label: 'Nilai Target', value: dataItem.value?.nilai_target || '-' },
        { label: 'Jenis Target', value: dataItem.value?.jenis_target || '-' },
        { label: 'Nama Program', value: dataItem.value?.program_latihan?.nama_program || '-' },
        {
            label: 'Periode',
            value:
                dataItem.value?.program_latihan?.periode_mulai && dataItem.value?.program_latihan?.periode_selesai
                    ? `${dataItem.value.program_latihan.periode_mulai} s/d ${dataItem.value.program_latihan.periode_selesai}`
                    : '-',
        },
    ];

    // Field peruntukan hanya untuk target individu
    if (dataItem.value?.jenis_target === 'individu') {
        baseFields.splice(1, 0, { label: 'Peruntukan', value: dataItem.value?.peruntukan || '-' });
    }

    return baseFields;
});

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data target latihan berhasil dihapus', variant: 'success' });
            router.visit(`/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}`);
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data target latihan', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Target Latihan"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="`/program-latihan/${programId}/target-latihan/${jenisTarget}`"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
