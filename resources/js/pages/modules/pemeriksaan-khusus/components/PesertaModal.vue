<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { Plus, Trash2 } from 'lucide-vue-next';
import axios from 'axios';
import { ref, computed } from 'vue';
import permissionService from '@/services/permissionService';
import SelectTableMultiple from '@/pages/modules/components/SelectTableMultiple.vue';

interface Peserta {
    id: number; // pemeriksaan_khusus_peserta id
    peserta_id: number; // id peserta asli
    nama: string;
    jenis_kelamin: string;
    usia: number | null;
    posisi_atlet?: string;
    jenis_pelatih?: string;
    jenis_tenaga_pendukung?: string;
}

interface Props {
    show: boolean;
    data: Peserta[];
    tipe: string;
    pemeriksaanKhususId: number | null;
}

interface Emits {
    (e: 'close'): void;
    (e: 'refresh'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { toast } = useToast();

const showDeleteDialog = ref(false);
const pesertaToDelete = ref<Peserta | null>(null);
const isDeleting = ref(false);

// Tambah peserta dialog
const showAddPesertaDialog = ref(false);
const selectedPesertaIds = ref<number[]>([]);
const isAdding = ref(false);

// Permission checks
const canTambahPeserta = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Tambah Peserta');
});

const canHapusPeserta = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Hapus Peserta');
});

const getTipeLabel = (tipe: string) => {
    switch (tipe) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga_pendukung':
            return 'Tenaga Pendukung';
        default:
            return 'Peserta';
    }
};

const getJenisKelaminLabel = (jenisKelamin: string) => {
    switch (jenisKelamin) {
        case 'L':
            return 'Laki-laki';
        case 'P':
            return 'Perempuan';
        default:
            return '-';
    }
};

const handleClose = () => {
    emit('close');
};

const handleDeleteClick = (peserta: Peserta) => {
    pesertaToDelete.value = peserta;
    showDeleteDialog.value = true;
};

const handleConfirmDelete = async () => {
    if (!pesertaToDelete.value || !props.pemeriksaanKhususId) return;
    
    isDeleting.value = true;
    try {
        await axios.delete(`/pemeriksaan-khusus/${props.pemeriksaanKhususId}/peserta/${pesertaToDelete.value.id}`);
        toast({ 
            title: `${getTipeLabel(props.tipe)} berhasil dihapus dari pemeriksaan khusus`, 
            variant: 'success' 
        });
        showDeleteDialog.value = false;
        pesertaToDelete.value = null;
        emit('refresh'); // Trigger refresh di parent
    } catch (error: any) {
        toast({ 
            title: error.response?.data?.message || 'Gagal menghapus peserta', 
            variant: 'destructive' 
        });
    } finally {
        isDeleting.value = false;
    }
};

const getPesertaRoute = (tipe: string, pesertaId: number) => {
    switch (tipe) {
        case 'atlet':
            return `/atlet/${pesertaId}`;
        case 'pelatih':
            return `/pelatih/${pesertaId}`;
        case 'tenaga_pendukung':
            return `/tenaga-pendukung/${pesertaId}`;
        default:
            return '#';
    }
};

const handleAddPeserta = () => {
    selectedPesertaIds.value = [];
    showAddPesertaDialog.value = true;
};

const handleSavePeserta = async () => {
    if (!selectedPesertaIds.value.length || !props.pemeriksaanKhususId) {
        toast({ 
            title: 'Pilih minimal 1 peserta', 
            variant: 'destructive' 
        });
        return;
    }

    isAdding.value = true;
    try {
        await axios.post(`/pemeriksaan-khusus/${props.pemeriksaanKhususId}/peserta`, {
            peserta_ids: selectedPesertaIds.value,
            jenis_peserta: props.tipe,
        });
        toast({ 
            title: `${selectedPesertaIds.value.length} ${getTipeLabel(props.tipe)} berhasil ditambahkan`, 
            variant: 'success' 
        });
        showAddPesertaDialog.value = false;
        selectedPesertaIds.value = [];
        emit('refresh'); // Trigger refresh di parent
    } catch (error: any) {
        toast({ 
            title: error.response?.data?.message || 'Gagal menambahkan peserta', 
            variant: 'destructive' 
        });
    } finally {
        isAdding.value = false;
    }
};

// Columns untuk SelectTableMultiple
const getColumns = () => {
    const baseColumns = [
        { key: 'jenis_kelamin', label: 'Jenis Kelamin', format: (row: any) => getJenisKelaminLabel(row.jenis_kelamin) },
        { key: 'usia', label: 'Usia', format: (row: any) => row.usia ? `${row.usia} tahun` : '-' },
    ];

    if (props.tipe === 'atlet') {
        return [{ key: 'nama', label: 'Nama Atlet' }, ...baseColumns];
    } else if (props.tipe === 'pelatih') {
        return [{ key: 'nama', label: 'Nama Pelatih' }, ...baseColumns];
    } else {
        return [{ key: 'nama', label: 'Nama Tenaga Pendukung' }, ...baseColumns];
    }
};
</script>

<template>
    <Dialog :open="show" @update:open="handleClose">
        <DialogContent class="max-h-[80vh] max-w-4xl overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center justify-between">
                    <DialogTitle class="text-xl font-semibold"> 
                        Daftar {{ getTipeLabel(tipe) }} 
                    </DialogTitle>
                    <Button v-if="canTambahPeserta" @click="handleAddPeserta" size="sm" class="mr-8">
                        <Plus class="mr-1 h-4 w-4" />
                        Tambah {{ getTipeLabel(tipe) }}
                    </Button>
                </div>
            </DialogHeader>

            <div class="mt-6">
                <table class="min-w-full border text-sm">
                    <thead>
                        <tr class="bg-muted">
                            <th class="w-12 border px-2 py-2 text-center">#</th>
                            <th class="border px-3 py-2 text-left">Nama</th>
                            <th class="border px-3 py-2 text-center">Jenis Kelamin</th>
                            <th class="border px-3 py-2 text-center">Usia</th>
                            <th v-if="canHapusPeserta" class="w-24 border px-3 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="data.length === 0">
                            <td :colspan="canHapusPeserta ? 5 : 4" class="text-muted-foreground py-4 text-center">
                                Tidak ada data {{ getTipeLabel(tipe) }} untuk pemeriksaan khusus ini
                            </td>
                        </tr>
                        <tr v-for="(peserta, idx) in data" :key="peserta.id" class="hover:bg-muted/50">
                            <td class="border px-2 py-2 text-center">{{ idx + 1 }}</td>
                            <td class="border px-3 py-2">
                                <div class="flex flex-col">
                                    <span 
                                        class="truncate cursor-pointer hover:text-primary font-medium" 
                                        :title="peserta.nama" 
                                        @click="() => router.visit(getPesertaRoute(tipe, peserta.peserta_id))"
                                    >
                                        {{ peserta.nama }}
                                    </span>
                                    <span v-if="tipe === 'atlet' && peserta.posisi_atlet" class="text-xs text-muted-foreground mt-1">
                                        {{ peserta.posisi_atlet }}
                                    </span>
                                    <span v-else-if="tipe === 'pelatih' && peserta.jenis_pelatih" class="text-xs text-muted-foreground mt-1">
                                        {{ peserta.jenis_pelatih }}
                                    </span>
                                    <span v-else-if="tipe === 'tenaga_pendukung' && peserta.jenis_tenaga_pendukung" class="text-xs text-muted-foreground mt-1">
                                        {{ peserta.jenis_tenaga_pendukung }}
                                    </span>
                                </div>
                            </td>
                            <td class="border px-3 py-2 text-center">
                                {{ getJenisKelaminLabel(peserta.jenis_kelamin) }}
                            </td>
                            <td class="border px-3 py-2 text-center">
                                {{ peserta.usia ? `${peserta.usia} tahun` : '-' }}
                            </td>
                            <td v-if="canHapusPeserta" class="border px-3 py-2 text-center">
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    @click="handleDeleteClick(peserta)"
                                    class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <Button variant="outline" @click="handleClose"> Tutup </Button>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :open="showDeleteDialog" @update:open="showDeleteDialog = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>Hapus Peserta dari Pemeriksaan Khusus?</AlertDialogTitle>
                <AlertDialogDescription>
                    Apakah Anda yakin ingin menghapus <strong>{{ pesertaToDelete?.nama }}</strong> dari pemeriksaan khusus ini?
                    <br>
                    <span class="text-sm text-muted-foreground">
                        Peserta akan dihapus dari pemeriksaan khusus, tetapi tetap ada di cabor.
                    </span>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel :disabled="isDeleting">Batal</AlertDialogCancel>
                <AlertDialogAction 
                    @click="handleConfirmDelete" 
                    :disabled="isDeleting"
                    class="bg-red-600 hover:bg-red-700"
                >
                    {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>

    <!-- Add Peserta Dialog -->
    <Dialog :open="showAddPesertaDialog" @update:open="showAddPesertaDialog = $event">
        <DialogContent class="max-h-[80vh] max-w-5xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="text-xl font-semibold">
                    Tambah {{ getTipeLabel(tipe) }}
                </DialogTitle>
            </DialogHeader>

            <div class="mt-4">
                <SelectTableMultiple
                    v-if="props.pemeriksaanKhususId"
                    :label="`Pilih ${getTipeLabel(tipe)}`"
                    :endpoint="`/api/pemeriksaan-khusus/${props.pemeriksaanKhususId}/peserta/available?jenis_peserta=${tipe}`"
                    :columns="getColumns()"
                    :id-key="'id'"
                    :name-key="'nama'"
                    v-model:selected-ids="selectedPesertaIds"
                />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <Button variant="outline" @click="showAddPesertaDialog = false" :disabled="isAdding">
                    Batal
                </Button>
                <Button @click="handleSavePeserta" :disabled="isAdding || selectedPesertaIds.length === 0">
                    {{ isAdding ? 'Menambahkan...' : `Tambah ${selectedPesertaIds.length} Peserta` }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

