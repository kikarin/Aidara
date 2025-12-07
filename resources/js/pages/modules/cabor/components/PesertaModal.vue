<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

interface Peserta {
    id: number;
    nama: string;
    foto: string | null;
    jenis_kelamin: string;
    usia: number | null;
}

interface Props {
    show: boolean;
    data: Peserta[];
    tipe: string;
    caborId: number | null;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

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

const handleAddPeserta = () => {
    if (props.caborId && props.tipe) {
        router.visit(`/cabor/${props.caborId}/peserta/${props.tipe}/create`);
    }
};
</script>

<template>
    <Dialog :open="show" @update:open="handleClose">
        <DialogContent class="max-h-[80vh] max-w-4xl overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center justify-between">
                    <DialogTitle class="text-xl font-semibold"> Daftar {{ getTipeLabel(tipe) }} </DialogTitle>
                    <Button @click="handleAddPeserta" size="sm" class="mr-8">
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
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="data.length === 0">
                            <td colspan="4" class="text-muted-foreground py-4 text-center">
                                Tidak ada data {{ getTipeLabel(tipe) }} untuk cabor ini
                            </td>
                        </tr>
                        <tr v-for="(peserta, idx) in data" :key="peserta.id" class="hover:bg-muted/50">
                            <td class="border px-2 py-2 text-center">{{ idx + 1 }}</td>
                            <td class="flex items-center space-x-3 border px-3 py-2">
                                <span class="truncate" :title="peserta.nama">{{ peserta.nama }}</span>
                            </td>
                            <td class="border px-3 py-2 text-center">
                                {{ getJenisKelaminLabel(peserta.jenis_kelamin) }}
                            </td>
                            <td class="border px-3 py-2 text-center">
                                {{ peserta.usia ? `${peserta.usia} tahun` : '-' }}
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
</template>
