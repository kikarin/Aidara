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

const formatTahap = (tahap: string) => {
    if (!tahap) return '-';
    const mapping: Record<string, string> = {
        'persiapan umum': 'Persiapan Umum',
        'persiapan khusus': 'Persiapan Khusus',
        'prapertandingan': 'Prapertandingan',
        'pertandingan': 'Pertandingan',
        'transisi': 'Transisi',
    };
    return mapping[tahap] || tahap;
};

const fields = computed(() => {
    const pelatihNames =
        dataItem.value?.pelatihs?.length > 0
            ? dataItem.value.pelatihs.map((p: any) => p.nama).join(', ')
            : dataItem.value?.pelatih?.nama || '-';

    const jamAbsen =
        dataItem.value?.absen_jam_mulai && dataItem.value?.absen_jam_selesai
            ? `${String(dataItem.value.absen_jam_mulai).slice(0, 5)} – ${String(dataItem.value.absen_jam_selesai).slice(0, 5)} WIB`
            : 'Bebas (tidak dibatasi jam)';

    return [
        { label: 'Nama Program', value: dataItem.value?.nama_program || '-' },
        { label: 'Cabor', value: dataItem.value?.cabor?.nama || '-' },
        { label: 'Kategori', value: dataItem.value?.cabor_kategori?.nama || '-' },
        {
            label: 'Mode Pelatih',
            value: dataItem.value?.mode_pelatih === 'multiple' ? 'Lebih dari satu pelatih' : 'Satu pelatih',
        },
        { label: 'Pelatih', value: pelatihNames },
        {
            label: 'Absen Atlet',
            value: dataItem.value?.wajib_absen_atlet ? 'Wajib absen' : 'Tidak wajib',
        },
        { label: 'Jam Absen', value: jamAbsen },
        {
            label: 'Periode',
            value:
                dataItem.value?.periode_mulai && dataItem.value?.periode_selesai
                    ? `${dataItem.value.periode_mulai} s/d ${dataItem.value.periode_selesai}`
                    : '-',
        },
        {
            label: 'Durasi',
            value: dataItem.value?.periode_hitung || '-',
        },
        {
            label: 'Tahap',
            value: formatTahap(dataItem.value?.tahap),
        },
        { label: 'Keterangan', value: dataItem.value?.keterangan || '-' },
    ];
});

const actionFields = [
    { label: 'Dibuat Pada', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Dibuat Oleh', value: props.item.created_by_user?.name || '-' },
    { label: 'Diperbarui Pada', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Diperbarui Oleh', value: props.item.updated_by_user?.name || '-' },
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
