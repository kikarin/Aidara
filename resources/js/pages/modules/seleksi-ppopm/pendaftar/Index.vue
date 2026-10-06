<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    caborOptions: { value: number; label: string }[];
    statusOptions: { value: string; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Seleksi PPOPM', href: '/seleksi-ppopm' },
    { title: 'Pendaftar', href: '/seleksi-ppopm/pendaftar' },
];

const columns = [
    { key: 'nomor_tes', label: 'Nomor tes' },
    { key: 'nama', label: 'Nama' },
    { key: 'cabor', label: 'Cabor', orderable: false },
    { key: 'jenis_kelamin_label', label: 'JK', orderable: false },
    { key: 'sekolah', label: 'Sekolah' },
    { key: 'map_label', label: 'Map', orderable: false },
    {
        key: 'status_label',
        label: 'Status',
        format: (row: any) => {
            const colors: Record<string, string> = {
                submitted: 'bg-amber-500',
                lulus_administrasi: 'bg-blue-500',
                lulus: 'bg-green-600',
                observasi: 'bg-orange-500',
                ditolak_admin: 'bg-red-600',
                tidak_lulus: 'bg-red-600',
                gugur_kesehatan: 'bg-red-600',
                gugur_antropometri: 'bg-red-600',
                gugur_kecabangan: 'bg-red-600',
            };
            const color = colors[row.status] || 'bg-muted-foreground';
            return `<span class="px-2 py-1 rounded text-white text-xs ${color}">${row.status_label}</span>`;
        },
    },
    { key: 'nilai_akhir', label: 'Nilai akhir' },
    { key: 'ranking', label: 'Ranking' },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/seleksi-ppopm/pendaftar/${row.id}`),
        permission: 'Seleksi PPOPM Detail',
    },
];
</script>

<template>
    <PageIndex
        title="Pendaftar Seleksi PPOPM"
        module-name="Seleksi PPOPM"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        create-url="/seleksi-ppopm/pendaftar/create"
        :actions="actions"
        :selected="selected"
        @update:selected="(val) => (selected = val)"
        api-endpoint="/api/seleksi-ppopm/pendaftar"
        ref="pageIndex"
        :on-toast="toast"
        :show-import="false"
        :show-delete="false"
        base-url="/seleksi-ppopm/pendaftar"
    />
</template>
