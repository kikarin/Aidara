<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const page = usePage();
const pelatih = computed(() => page.props.pelatih || {}) as any;
const pelatihId = computed(() => pelatih.value.id);

const { toast } = useToast();
const pageIndex = ref();

const breadcrumbs = [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Riwayat Pemeriksaan', href: `/pelatih/${pelatihId.value}/riwayat-pemeriksaan` },
];

const columns = [
    {
        key: 'nama_pemeriksaan',
        label: 'Nama Pemeriksaan',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => row.nama_pemeriksaan || '-',
    },
    {
        key: 'tanggal_pemeriksaan',
        label: 'Tanggal Pemeriksaan',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => {
            if (!row.tanggal_pemeriksaan) return '-';
            return new Date(row.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });
        },
    },
    {
        key: 'tenaga_pendukung',
        label: 'Tenaga Pendukung',
        searchable: false,
        orderable: false,
        visible: true,
        format: (row: any) => row.tenaga_pendukung || '-',
    },
    {
        key: 'parameter',
        searchable: false,
        orderable: false,
        visible: true,
        label: 'Parameter',
    },
];

const actions = (row: any) => [
    {
        label: 'Detail Parameter',
        onClick: () => showParameterDetail(row),
    },
];

const showParameterDetail = (row: any) => {
    // Redirect ke halaman detail parameter
    router.visit(`/pelatih/${pelatihId.value}/riwayat-pemeriksaan/${row.id}/parameter`);
};

const selected = ref<number[]>([]);

const deleteSelected = async () => {
    // Tidak ada fungsi delete untuk riwayat pemeriksaan
    toast({ title: 'Fitur hapus tidak tersedia untuk riwayat pemeriksaan', variant: 'destructive' });
};
</script>

<template>
    <PageIndex
        :title="`Riwayat Pemeriksaan - ${pelatih.nama}`"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :api-endpoint="`/api/pelatih/${pelatihId}/riwayat-pemeriksaan`"
        ref="pageIndex"
        :on-toast="toast"
        :showCreate="false"
        :showImport="false"
        :showDelete="true"
        :disable-length="true"
        :hide-pagination="true"
        :hide-search="true"
        :hide-select-all="true"
        :hide-select="true"
    >
        <template #header-extra>
            <div class="bg-card mb-4 rounded-lg border p-4">
                <h3 class="mb-2 text-lg font-semibold">Informasi Pelatih</h3>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Nama:</span>
                        <span class="text-sm font-medium">{{ pelatih.nama }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">NIK:</span>
                        <span class="text-sm font-medium">{{ pelatih.nik }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground text-sm font-medium">Jenis Kelamin:</span>
                        <span class="text-sm font-medium">{{ pelatih.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #cell-parameter="{ row }">
            <BadgeGroup
                :badges="[
                    {
                        value: row.jumlah_parameter || 0,
                        colorClass: 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200',
                        onClick: () => showParameterDetail(row),
                    },
                ]"
            />
        </template>
    </PageIndex>
</template>
