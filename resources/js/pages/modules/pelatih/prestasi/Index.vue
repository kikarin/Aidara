<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import HeaderActions from '@/pages/modules/base-page/HeaderActions.vue';
import DataTable from '@/pages/modules/components/DataTable.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash.debounce';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{ pelatihId: number }>();
const { toast } = useToast();
const inertiaPage = usePage();

// Ambil user registration_status dari props
const user = computed(() => (inertiaPage.props as any)?.auth?.user);
const registrationStatus = computed(() => user.value?.registration_status);
const isPendingRegistration = computed(() => registrationStatus.value === 'pending');

const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Prestasi', href: `/pelatih/${props.pelatihId}/prestasi` },
]);

const columns = [
    { key: 'nama_event', label: 'Nama Event' },
    { key: 'tingkat', label: 'Tingkat', format: (row: any) => row.tingkat?.nama || '-' },
    {
        key: 'tanggal',
        label: 'Tanggal',
        format: (row: any) =>
            row.tanggal ? new Date(row.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'numeric', year: 'numeric' }) : '-',
    },
    { key: 'peringkat', label: 'Peringkat' },
    { key: 'keterangan', label: 'Keterangan' },
];

const selected = ref<number[]>([]);
const rows = ref<any[]>([]);
const total = ref(0);
const loading = ref(false);
const search = ref('');
const page = ref(1);
const perPage = ref(10);
const sort = ref<{ key: string; order: 'asc' | 'desc' }>({ key: '', order: 'asc' });
const handleSearchDebounced = debounce((val: string) => {
    search.value = val;
    fetchData();
}, 400);

const handleSort = debounce((val: { key: string; order: 'asc' | 'desc' }) => {
    sort.value.key = val.key;
    sort.value.order = val.order;
    page.value = 1;
    fetchData();
}, 400);

const handleSearch = (params: { search?: string; sortKey?: string; sortOrder?: 'asc' | 'desc'; page?: number; limit?: number }) => {
    if (params.search !== undefined) search.value = params.search;
    if (params.sortKey) sort.value.key = params.sortKey;
    if (params.sortOrder) sort.value.order = params.sortOrder;
    if (params.page) page.value = params.page;
    if (params.limit) perPage.value = params.limit;
};

const fetchData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/pelatih/${props.pelatihId}/prestasi`, {
            params: {
                search: search.value,
                page: page.value,
                per_page: perPage.value,
                sort: sort.value.key,
                order: sort.value.order,
            },
        });
        rows.value = response.data.data;
        const meta = response.data.meta || {};
        total.value = Number(meta.total) || 0;
        page.value = Number(meta.current_page) || 1;
        perPage.value = Number(meta.per_page) || 10;
        search.value = meta.search || '';
        sort.value.key = meta.sort || '';
        sort.value.order = meta.order || 'asc';
    } catch {
        toast({ title: 'Gagal memuat data', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);

watch([page, perPage, () => sort.value.key, () => sort.value.order], () => {
    fetchData();
});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pelatih/${props.pelatihId}/prestasi/${row.id}`),
        permission: 'Pelatih Prestasi Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pelatih/${props.pelatihId}/prestasi/${row.id}/edit`),
        permission: 'Pelatih Prestasi Edit',
    },
    {
        label: 'Delete',
        onClick: () => handleDeleteRow(row),
        permission: 'Pelatih Prestasi Delete',
    },
];

const handleDeleteRow = async (row: any) => {
    rowToDelete.value = row;
    showDeleteDialog.value = true;
};

const confirmDeleteRow = async () => {
    if (!rowToDelete.value) return;

    router.delete(`/pelatih/${props.pelatihId}/prestasi/${rowToDelete.value.id}`, {
        onSuccess: () => {
            toast({ title: 'Prestasi berhasil dihapus', variant: 'success' });
            fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus prestasi.', variant: 'destructive' });
        },
    });
    showDeleteDialog.value = false;
    rowToDelete.value = null;
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    idsToDelete.value = [...selected.value];
    showDeleteSelectedDialog.value = true;
};

const confirmDeleteSelected = async () => {
    try {
        const response = await axios.post(`/pelatih/${props.pelatihId}/prestasi/destroy-selected`, { ids: idsToDelete.value });
        selected.value = [];
        fetchData();
        toast({ title: response.data?.message || 'Prestasi terpilih berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus prestasi terpilih', variant: 'destructive' });
    }
    showDeleteSelectedDialog.value = false;
    idsToDelete.value = [];
};

// Tabs config - FILTER berdasarkan registration_status
const tabsConfig = computed(() => {
    const allTabs = [
        {
            value: 'pelatih-data',
            label: 'Pelatih',
            onClick: () => router.visit(`/pelatih/${props.pelatihId}/edit?tab=pelatih-data`),
            allowedForPending: true, // Data diri bisa diakses
        },
        {
            value: 'sertifikat-data',
            label: 'Sertifikat',
            onClick: () => router.visit(`/pelatih/${props.pelatihId}/sertifikat`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'prestasi-data',
            label: 'Prestasi',
            // Aktif - tidak perlu onClick
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'dokumen-data',
            label: 'Dokumen',
            onClick: () => router.visit(`/pelatih/${props.pelatihId}/dokumen`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'kesehatan-data',
            label: 'Kesehatan',
            onClick: () => router.visit(`/pelatih/${props.pelatihId}/edit?tab=kesehatan-data`),
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
        {
            value: 'akun-data',
            label: 'Akun',
            onClick: () => router.visit(`/pelatih/${props.pelatihId}/edit?tab=akun-data`),
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
    ];

    // Filter tab berdasarkan registration_status
    if (isPendingRegistration.value) {
        return allTabs
            .filter(tab => tab.allowedForPending === true)
            .map(tab => ({
                ...tab,
                disabled: false,
            }));
    }

    // Jika sudah approved, semua tab bisa diakses
    return allTabs.map(tab => ({
        ...tab,
        disabled: false,
    }));
});
const activeTab = ref('prestasi-data');

function handleTabChange(val: string) {
    if (val === 'prestasi-data') return;
    const tab = tabsConfig.value.find((t) => t.value === val);
    if (tab && tab.onClick) tab.onClick();
}

const showDeleteDialog = ref(false);
const showDeleteSelectedDialog = ref(false);
const rowToDelete = ref<any>(null);
const idsToDelete = ref<number[]>([]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen w-full bg-gray-100 dark:bg-neutral-950">
            <div class="container mx-auto">
                <div class="mx-auto px-4 py-4">
                    <!-- Tabs -->
                    <div class="mb-4">
                        <AppTabs :tabs="tabsConfig" :model-value="activeTab" @update:model-value="handleTabChange" :default-value="'prestasi-data'" />
                    </div>

                    <HeaderActions
                        title="Prestasi"
                        module-name="Pelatih Prestasi"
                        :create-url="`/pelatih/${props.pelatihId}/prestasi/create`"
                        :selected="selected"
                        :on-delete-selected="deleteSelected"
                    />
                </div>

                <div class="mx-4 rounded-xl bg-white pt-4 shadow dark:bg-neutral-900">
                    <DataTable
                        :columns="columns"
                        :rows="rows"
                        v-model:selected="selected"
                        :total="total"
                        :search="search"
                        :sort="sort"
                        :page="page"
                        :per-page="perPage"
                        :loading="loading"
                        :actions="actions"
                        @update:search="handleSearchDebounced"
                        @update:sort="handleSort"
                        @update:page="(val: number) => handleSearch({ page: val })"
                        @update:per-page="(val: number) => handleSearch({ limit: Number(val), page: 1 })"
                        @deleted="fetchData()"
                    />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus data ini!</DialogTitle>
                    <DialogDescription> Apakah Anda yakin? </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false">Batal</Button>
                    <Button variant="destructive" @click="confirmDeleteRow">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Delete Selected Confirmation Dialog -->
        <Dialog v-model:open="showDeleteSelectedDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus data terpilih?</DialogTitle>
                    <DialogDescription>
                        Anda akan menghapus <b>{{ idsToDelete.length }}</b> data. Tindakan ini tidak dapat dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteSelectedDialog = false">Batal</Button>
                    <Button variant="destructive" @click="confirmDeleteSelected">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
