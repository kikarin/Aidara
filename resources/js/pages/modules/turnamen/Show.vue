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
        foto: string | null;
        cabor_kategori_id: number;
        cabor_kategori: {
            id: number;
            nama: string;
            cabor: {
                id: number;
                nama: string;
            };
        };
        tanggal_mulai: string;
        tanggal_selesai: string;
        tingkat_id: number;
        tingkat: {
            id: number;
            nama: string;
        };
        lokasi: string;
        juara_id: number | null;
        juara: {
            id: number;
            nama: string;
        } | null;
        hasil: string;
        evaluasi: string;
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
    { title: 'Turnamen', href: '/turnamen' },
    { title: 'Detail Turnamen', href: `/turnamen/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Nama Turnamen', value: dataItem.value?.nama || '-' },
    {
        label: 'Foto Turnamen',
        value: dataItem.value?.foto ? `/storage/${dataItem.value.foto}` : '',
        type: 'image' as const,
        className: 'sm:col-span-2',
        imageConfig: {
            size: 'md' as const,
            labelText: 'Klik untuk melihat lebih besar',
        },
    },
    {
        label: 'Cabor Kategori',
        value: dataItem.value?.cabor_kategori ? `${dataItem.value.cabor_kategori.cabor.nama} - ${dataItem.value.cabor_kategori.nama}` : '-',
    },
    { label: 'Tanggal Mulai', value: dataItem.value?.tanggal_mulai ? new Date(dataItem.value.tanggal_mulai).toLocaleDateString('id-ID') : '-' },
    { label: 'Tanggal Selesai', value: dataItem.value?.tanggal_selesai ? new Date(dataItem.value.tanggal_selesai).toLocaleDateString('id-ID') : '-' },
    { label: 'Tingkat', value: dataItem.value?.tingkat?.nama || '-' },
    { label: 'Lokasi', value: dataItem.value?.lokasi || '-' },
    { label: 'Juara', value: dataItem.value?.juara?.nama || '-' },
    { label: 'Hasil', value: dataItem.value?.hasil || '-' },
    { label: 'Evaluasi', value: dataItem.value?.evaluasi || '-' },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/turnamen/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/turnamen/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data turnamen berhasil dihapus', variant: 'success' });
            router.visit('/turnamen');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data turnamen', variant: 'destructive' });
        },
    });
};

const handlePeserta = () => {
    router.visit(`/turnamen/${props.item.id}/peserta`);
};
</script>

<template>
    <PageShow
        title="Turnamen"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :actionFields="actionFields"
        :back-url="'/turnamen'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    >
        <template #custom-action>
            <button
                class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                @click="handlePeserta"
            >
                Peserta
            </button>
        </template>
    </PageShow>
</template>
