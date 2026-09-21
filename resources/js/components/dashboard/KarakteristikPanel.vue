<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { TableSkeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { Calendar } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    apiEndpoint: string;
    pesertaLabel: string;
}>();

const { toast } = useToast();

const tanggalAwal = ref('');
const tanggalAkhir = ref('');
const loading = ref(false);
const dataKarakteristik = ref<any[]>([]);

const fetchKarakteristik = async () => {
    loading.value = true;
    try {
        const response = await axios.post(props.apiEndpoint, {
            tanggal_awal: tanggalAwal.value,
            tanggal_akhir: tanggalAkhir.value,
        });

        if (response.data.success) {
            dataKarakteristik.value = response.data.data;
        } else {
            toast({ title: response.data.message || 'Gagal mengambil data', variant: 'destructive' });
        }
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Terjadi kesalahan', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

const triggerDatePicker = (fieldId: string) => {
    const dateInput = document.getElementById(fieldId) as HTMLInputElement;
    if (dateInput?.showPicker) {
        dateInput.showPicker();
    }
};

onMounted(() => {
    fetchKarakteristik();
});
</script>

<template>
    <div class="space-y-6">
        <Card>
            <div class="flex flex-col gap-4 px-6 py-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <CardTitle>Filter Data</CardTitle>
                    <CardDescription>Pilih rentang tanggal untuk menganalisis data karakteristik {{ pesertaLabel }}</CardDescription>
                </div>

                <div class="flex flex-col items-end gap-4 sm:flex-row">
                    <div class="space-y-2">
                        <Label :for="`tanggal_awal-${apiEndpoint}`">Tanggal Awal</Label>
                        <div class="relative">
                            <Input
                                :id="`tanggal_awal-${apiEndpoint}`"
                                v-model="tanggalAwal"
                                type="date"
                                class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                @click="triggerDatePicker(`tanggal_awal-${apiEndpoint}`)"
                            >
                                <Calendar class="text-muted-foreground h-4 w-4" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-muted-foreground">s.d</span>
                    </div>

                    <div class="space-y-2">
                        <Label :for="`tanggal_akhir-${apiEndpoint}`">Tanggal Akhir</Label>
                        <div class="relative">
                            <Input
                                :id="`tanggal_akhir-${apiEndpoint}`"
                                v-model="tanggalAkhir"
                                type="date"
                                class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                @click="triggerDatePicker(`tanggal_akhir-${apiEndpoint}`)"
                            >
                                <Calendar class="text-muted-foreground h-4 w-4" />
                            </div>
                        </div>
                    </div>

                    <Button @click="fetchKarakteristik" :disabled="loading">
                        {{ loading ? 'Mencari...' : 'Cari' }}
                    </Button>
                </div>
            </div>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Jumlah Karakteristik</CardTitle>
                <CardDescription>Tabel detail karakteristik {{ pesertaLabel }} berdasarkan kategori</CardDescription>
            </CardHeader>
            <CardContent>
                <TableSkeleton
                    v-if="loading"
                    :rows="6"
                    :column-labels="['#', 'Karakteristik', 'Indikator', 'Jumlah', 'Persentase']"
                />

                <div v-else-if="dataKarakteristik.length === 0" class="text-muted-foreground py-8 text-center">Tidak ada data karakteristik</div>

                <div v-else class="overflow-x-auto">
                    <Table>
                        <TableHeader class="bg-muted">
                            <TableRow>
                                <TableHead class="w-[5%]">#</TableHead>
                                <TableHead class="w-[20%]">Karakteristik</TableHead>
                                <TableHead class="w-[50%]">Indikator</TableHead>
                                <TableHead class="w-[12%] text-center">Jumlah</TableHead>
                                <TableHead class="w-[13%] text-center">Persentase</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="(karakteristik, index) in dataKarakteristik" :key="karakteristik.key">
                                <template v-for="(item, itemIndex) in karakteristik.data" :key="`${karakteristik.key}-${itemIndex}`">
                                    <TableRow>
                                        <TableCell class="font-medium">
                                            {{ itemIndex === 0 ? index + 1 : '' }}
                                        </TableCell>
                                        <TableCell class="font-medium">
                                            {{ itemIndex === 0 ? karakteristik.name : '' }}
                                        </TableCell>
                                        <TableCell>{{ item.nama_indikator }}</TableCell>
                                        <TableCell class="text-center">{{ item.jumlah }}</TableCell>
                                        <TableCell class="text-center">
                                            <span class="text-primary font-bold">{{ item.persentase }}%</span>
                                        </TableCell>
                                    </TableRow>
                                </template>

                                <TableRow v-if="index < dataKarakteristik.length - 1" class="bg-muted">
                                    <TableCell colspan="5" class="h-8"></TableCell>
                                </TableRow>
                            </template>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
