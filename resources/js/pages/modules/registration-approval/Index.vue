<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [{ title: 'Persetujuan Registrasi', href: '/registration-approval' }];

const columns = [
    { key: 'user_name', label: 'Nama' },
    { key: 'user_email', label: 'Email' },
    {
        key: 'peserta_type_label',
        label: 'Jenis Peserta',
    },
    {
        key: 'status_label',
        label: 'Status',
        format: (row: any) => {
            const statusColors: Record<string, string> = {
                'Menunggu Persetujuan': 'bg-yellow-100 text-yellow-800',
                Disetujui: 'bg-green-100 text-green-800',
                Ditolak: 'bg-red-100 text-red-800',
            };
            const color = statusColors[row.status_label] || 'bg-gray-100 text-gray-800';
            return `<span class="px-2 py-1 text-xs font-semibold rounded-full ${color}">${row.status_label}</span>`;
        },
        orderable: false,
    },
    {
        key: 'created_at_formatted',
        label: 'Tanggal Pengajuan',
    },
];

const selected = ref<number[]>([]);
const { toast } = useToast();

// Filter state
const showFilterModal = ref(false);
const currentFilters = ref<any>({});
const filterForm = ref({
    status: '',
    peserta_type: '',
    date_from: '',
    date_to: '',
});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/registration-approval/${row.id}`),
        permission: 'Registration Approval Detail',
    },
    {
        label: 'Setujui',
        onClick: () => handleApprove(row.id),
        permission: 'Registration Approval Add',
        show: row.status === 'submitted' || row.status === 'rejected',
    },
    {
        label: 'Tolak',
        onClick: () => handleReject(row.id),
        permission: 'Registration Approval Add',
        show: row.status === 'submitted',
    },
    {
        label: 'Delete',
        onClick: () => handleDeleteRow(row),
        permission: 'Registration Approval Delete',
        show: row.status !== 'approved', // Hanya bisa delete jika belum approved
    },
];

const pageIndex = ref();

// Approve/Reject dialogs
const showApproveDialog = ref(false);
const showRejectDialog = ref(false);
const selectedId = ref<number | null>(null);
const selectedIds = ref<number[]>([]);
const rejectReason = ref('');

const handleApprove = (id: number) => {
    selectedId.value = id;
    selectedIds.value = [id];
    showApproveDialog.value = true;
};

const handleReject = (id: number) => {
    selectedId.value = id;
    selectedIds.value = [id];
    rejectReason.value = '';
    showRejectDialog.value = true;
};

const confirmApprove = async () => {
    if (!selectedId.value || selectedIds.value.length === 0) {
        toast({ title: 'Pilih data yang akan disetujui', variant: 'destructive' });
        return;
    }

    try {
        const response = await axios.post(`/registration-approval/${selectedId.value}/approve`, {
            ids: selectedIds.value,
        });
        toast({ title: response.data?.message || 'Pengajuan berhasil disetujui', variant: 'success' });
        showApproveDialog.value = false;
        selectedId.value = null;
        selectedIds.value = [];
        selected.value = []; // Clear selected checkboxes
        pageIndex.value?.fetchData();
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menyetujui pengajuan', variant: 'destructive' });
    }
};

const confirmReject = async () => {
    if (!selectedId.value || selectedIds.value.length === 0) {
        toast({ title: 'Pilih data yang akan ditolak', variant: 'destructive' });
        return;
    }

    if (!rejectReason.value.trim()) {
        toast({ title: 'Alasan penolakan wajib diisi', variant: 'destructive' });
        return;
    }

    try {
        const response = await axios.post(`/registration-approval/${selectedId.value}/reject`, {
            ids: selectedIds.value,
            rejected_reason: rejectReason.value,
        });
        toast({ title: response.data?.message || 'Pengajuan berhasil ditolak', variant: 'success' });
        showRejectDialog.value = false;
        selectedId.value = null;
        selectedIds.value = [];
        selected.value = []; // Clear selected checkboxes
        rejectReason.value = '';
        pageIndex.value?.fetchData();
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menolak pengajuan', variant: 'destructive' });
    }
};

// Bulk approve/reject
const handleBulkApprove = () => {
    if (selected.value.length === 0) {
        toast({ title: 'Pilih data yang akan disetujui', variant: 'destructive' });
        return;
    }
    selectedIds.value = selected.value;
    selectedId.value = selected.value[0];
    showApproveDialog.value = true;
};

const handleBulkReject = () => {
    if (selected.value.length === 0) {
        toast({ title: 'Pilih data yang akan ditolak', variant: 'destructive' });
        return;
    }
    selectedIds.value = selected.value;
    selectedId.value = selected.value[0];
    rejectReason.value = '';
    showRejectDialog.value = true;
};

const openFilterModal = () => {
    filterForm.value = {
        status: currentFilters.value.status || '',
        peserta_type: currentFilters.value.peserta_type || '',
        date_from: currentFilters.value.date_from || '',
        date_to: currentFilters.value.date_to || '',
    };
    showFilterModal.value = true;
};

const applyFilters = () => {
    const filters: Record<string, string> = {};
    if (filterForm.value.status) filters.status = filterForm.value.status;
    if (filterForm.value.peserta_type) filters.peserta_type = filterForm.value.peserta_type;
    if (filterForm.value.date_from) filters.date_from = filterForm.value.date_from;
    if (filterForm.value.date_to) filters.date_to = filterForm.value.date_to;

    currentFilters.value = filters;
    pageIndex.value?.handleFilterFromParent(filters);
    showFilterModal.value = false;
    toast({ title: 'Filter berhasil diterapkan', variant: 'success' });
};

const resetFilters = () => {
    filterForm.value = {
        status: '',
        peserta_type: '',
        date_from: '',
        date_to: '',
    };
    currentFilters.value = {};
    pageIndex.value?.handleFilterFromParent({});
    showFilterModal.value = false;
};

// Delete handlers
const handleDeleteRow = async (row: any) => {
    if (row.status === 'approved') {
        toast({ title: 'Tidak dapat menghapus pengajuan yang sudah disetujui', variant: 'destructive' });
        return;
    }

    try {
        await router.delete(`/registration-approval/${row.id}`, {
            onSuccess: () => {
                toast({ title: 'Pengajuan berhasil dihapus', variant: 'success' });
                pageIndex.value?.fetchData();
            },
            onError: () => {
                toast({ title: 'Gagal menghapus pengajuan', variant: 'destructive' });
            },
        });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus pengajuan', variant: 'destructive' });
    }
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
        return;
    }

    try {
        const response = await axios.post('/registration-approval/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value?.fetchData();

        toast({
            title: response.data?.message || 'Data berhasil dihapus',
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);

        const message = error.response?.data?.message || 'Gagal menghapus data';
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <PageIndex
        ref="pageIndex"
        title="Persetujuan Registrasi"
        module-name="Registration Approval"
        api-endpoint="/api/registration-approval"
        :columns="columns"
        :breadcrumbs="breadcrumbs"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :show-filter="true"
        :show-bulk-approve="true"
        :show-bulk-reject="true"
        :show-import="false"
        :show-create="false"
        :show-delete="false"
        :custom-filters="currentFilters"
        :on-delete-selected="deleteSelected"
        :on-delete-row="handleDeleteRow"
        @filter="openFilterModal"
        @bulk-approve="handleBulkApprove"
        @bulk-reject="handleBulkReject"
    >
    </PageIndex>

    <!-- Filter Dialog -->
    <Dialog :open="showFilterModal" @update:open="(val: boolean) => (showFilterModal = val)">
        <DialogContent class="sm:max-w-[480px]">
            <DialogHeader>
                <DialogTitle>Filter Persetujuan Registrasi</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div>
                    <Label for="filter_status">Status</Label>
                    <Select v-model="filterForm.status">
                        <SelectTrigger id="filter_status">
                            <SelectValue placeholder="Pilih Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Status</SelectItem>
                            <SelectItem value="submitted">Menunggu Persetujuan</SelectItem>
                            <SelectItem value="approved">Disetujui</SelectItem>
                            <SelectItem value="rejected">Ditolak</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label for="filter_peserta_type">Jenis Peserta</Label>
                    <Select v-model="filterForm.peserta_type">
                        <SelectTrigger id="filter_peserta_type">
                            <SelectValue placeholder="Pilih Jenis Peserta" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Semua Jenis Peserta</SelectItem>
                            <SelectItem value="atlet">Atlet</SelectItem>
                            <SelectItem value="pelatih">Pelatih</SelectItem>
                            <SelectItem value="tenaga_pendukung">Tenaga Pendukung</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <Label for="filter_date_from">Dari Tanggal</Label>
                        <Input id="filter_date_from" v-model="filterForm.date_from" type="date" />
                    </div>
                    <div>
                        <Label for="filter_date_to">Sampai Tanggal</Label>
                        <Input id="filter_date_to" v-model="filterForm.date_to" type="date" />
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="resetFilters">Reset</Button>
                <Button @click="applyFilters">Terapkan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Approve Dialog -->
    <Dialog :open="showApproveDialog" @update:open="showApproveDialog = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Setujui Pengajuan Registrasi</DialogTitle>
                <DialogDescription>
                    Apakah Anda yakin ingin menyetujui {{ selectedIds.length > 1 ? `${selectedIds.length} pengajuan` : 'pengajuan ini' }}?
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showApproveDialog = false">Batal</Button>
                <Button @click="confirmApprove">Setujui</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Reject Dialog -->
    <Dialog :open="showRejectDialog" @update:open="showRejectDialog = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Tolak Pengajuan Registrasi</DialogTitle>
                <DialogDescription>
                    Masukkan alasan penolakan untuk {{ selectedIds.length > 1 ? `${selectedIds.length} pengajuan` : 'pengajuan ini' }}.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div>
                    <Label for="reject_reason">Alasan Penolakan *</Label>
                    <Input id="reject_reason" v-model="rejectReason" placeholder="Masukkan alasan penolakan" class="mt-2" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showRejectDialog = false">Batal</Button>
                <Button variant="destructive" @click="confirmReject">Tolak</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
