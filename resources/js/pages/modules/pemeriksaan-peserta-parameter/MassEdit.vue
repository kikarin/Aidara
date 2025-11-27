<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    pemeriksaan: {
        id: number;
        nama: string;
        cabor: string;
        kategori: string;
        tenaga_pendukung: string;
    };
    jenis_peserta: string;
}>();

const { toast } = useToast();

const pesertaList = ref<any[]>([]);
const parameterList = ref<any[]>([]);
const tableState = ref<any[]>([]);
const statusList = ref<any[]>([]);

const trendOptions = [
    { value: 'stabil', label: 'Stabil' },
    { value: 'kenaikan', label: 'Kenaikan' },
    { value: 'penurunan', label: 'Penurunan' },
];

const loading = ref(false);

const calculateAge = (birthDate: string) => {
    if (!birthDate) return '-';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};

onMounted(async () => {
    try {
        loading.value = true;

        // Fetch status pemeriksaan
        const statusRes = await axios.get('/api/ref-status-pemeriksaan');
        statusList.value = (statusRes.data || []).map((status: any) => ({
            value: status.id,
            label: status.nama,
        }));

        // Fetch parameter pemeriksaan (pass jenis_peserta for filtering)
        const paramRes = await axios.get(`/api/pemeriksaan/${props.pemeriksaan.id}/pemeriksaan-parameter`, {
            params: { jenis_peserta: props.jenis_peserta },
        });
        parameterList.value = paramRes.data?.data || [];

        // Fetch peserta berdasarkan jenis
        const pesertaRes = await axios.get(`/api/pemeriksaan/${props.pemeriksaan.id}/peserta/${props.jenis_peserta}`);
        pesertaList.value = pesertaRes.data?.data || pesertaRes.data?.items?.data || [];

        // Init table state
        tableState.value = pesertaList.value.map((peserta) => {
            const jenisKelamin = peserta.peserta?.jenis_kelamin === 'P' ? 'Perempuan' : peserta.peserta?.jenis_kelamin === 'L' ? 'Laki-laki' : '-';

            return {
                peserta_id: peserta.id,
                peserta: {
                    nama: peserta.peserta?.nama || '-',
                    jenis_kelamin: jenisKelamin,
                    usia: calculateAge(peserta.peserta?.tanggal_lahir),
                },
                status: peserta.status?.id || peserta.ref_status_pemeriksaan_id || '',
                catatan: peserta.catatan_umum || '',
                parameters: parameterList.value.map((param) => {
                    const found = (peserta.pemeriksaanPesertaParameter || []).find((d: any) => d.pemeriksaan_parameter_id == param.id);
                    return {
                        parameter_id: param.id,
                        nilai: found ? found.nilai : '',
                        trend: found ? found.trend : 'stabil',
                    };
                }),
            };
        });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal memuat data', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
});

const handleSave = async () => {
    try {
        loading.value = true;
        const payload = {
            data: tableState.value.map((row) => ({
                peserta_id: row.peserta_id,
                status: row.status,
                catatan: row.catatan,
                parameters: row.parameters.map((param: any) => ({
                    parameter_id: param.parameter_id,
                    nilai: param.nilai,
                    trend: param.trend,
                })),
            })),
        };
        const response = await axios.post(`/pemeriksaan/${props.pemeriksaan.id}/peserta-parameter/bulk-update`, payload);
        if (response.data?.success) {
            toast({ title: response.data?.message || 'Data berhasil disimpan', variant: 'success' });
        }
    } catch (error: any) {
        // User friendly error message
        const msg = error.response?.data?.message || 'Gagal menyimpan data, pastikan semua nilai sudah diisi!';
        toast({ title: msg, variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { title: 'Pemeriksaan', href: '/pemeriksaan' },
            { title: 'Pemetaan Parameter Peserta', href: '#' },
        ]"
    >
        <div class="min-h-screen w-full bg-gray-100 pt-4 dark:bg-neutral-950">
            <div class="mx-auto max-w-7xl px-4">
                <!-- Info Card -->
                <div class="bg-card mb-4 rounded-lg border p-4">
                    <h3 class="mb-2 text-lg font-semibold">Informasi Pemeriksaan</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Nama Pemeriksaan:</span>
                            <span class="text-sm font-medium">{{ props.pemeriksaan.nama }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                            <span class="text-sm font-medium">{{ props.pemeriksaan.cabor }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Kategori:</span>
                            <span class="text-sm font-medium">{{ props.pemeriksaan.kategori }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Tenaga Pendukung:</span>
                            <span class="text-sm font-medium">{{ props.pemeriksaan.tenaga_pendukung }}</span>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mb-4 flex justify-center">
                    <Button variant="default" size="lg" @click="handleSave" :disabled="loading">
                        {{ loading ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </div>

                <!-- Table Section -->
                <div class="text-foreground mb-2 text-lg font-semibold">
                    Peserta {{ props.jenis_peserta.charAt(0).toUpperCase() + props.jenis_peserta.slice(1) }}
                </div>

                <!-- Table -->
                <div v-if="tableState.length && parameterList.length" class="overflow-x-auto rounded-xl bg-white shadow dark:bg-neutral-900">
                    <table class="w-full min-w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-muted">
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Nama</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Jenis Kelamin</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Usia</th>
                                <template v-for="param in parameterList" :key="'param-header-' + param.id">
                                    <th class="text-foreground border-b px-3 py-2 text-center whitespace-nowrap" :colspan="2">
                                        {{ param.nama_parameter }}
                                        <div class="text-muted-foreground text-xs">{{ param.satuan }}</div>
                                    </th>
                                </template>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Status Pemeriksaan</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Catatan</th>
                            </tr>
                            <tr class="bg-muted">
                                <template v-for="param in parameterList" :key="'param-subheader-' + param.id">
                                    <th class="text-foreground border-b px-2 py-1 whitespace-nowrap">Nilai</th>
                                    <th class="text-foreground border-b px-2 py-1 whitespace-nowrap">Trend</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIdx) in tableState"
                                :key="'peserta-row-' + row.peserta_id"
                                class="hover:bg-muted/40 border-t transition"
                            >
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ row.peserta.nama }}</td>
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ row.peserta.jenis_kelamin }}</td>
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ row.peserta.usia }}</td>

                                <template v-for="(param, paramIdx) in row.parameters" :key="'param-' + param.parameter_id">
                                    <td class="border-b px-2 py-1 whitespace-nowrap">
                                        <Input
                                            type="text"
                                            class="bg-background text-foreground w-24 rounded border px-1 py-0.5 text-right"
                                            v-model="tableState[rowIdx].parameters[paramIdx].nilai"
                                            placeholder="0"
                                        />
                                    </td>
                                    <td class="border-b px-2 py-1 whitespace-nowrap">
                                        <SimpleSelect
                                            :model-value="tableState[rowIdx].parameters[paramIdx].trend"
                                            @update:modelValue="(val: any) => (tableState[rowIdx].parameters[paramIdx].trend = val)"
                                            :options="trendOptions"
                                            placeholder="Pilih trend"
                                        />
                                    </td>
                                </template>

                                <td class="border-b px-2 py-1 whitespace-nowrap">
                                    <SimpleSelect
                                        :model-value="tableState[rowIdx].status"
                                        @update:modelValue="(val: any) => (tableState[rowIdx].status = val)"
                                        :options="statusList"
                                        placeholder="Pilih status"
                                    />
                                </td>
                                <td class="border-b px-2 py-1 whitespace-nowrap">
                                    <Input
                                        type="text"
                                        class="bg-background text-foreground w-full rounded border px-1 py-0.5"
                                        v-model="tableState[rowIdx].catatan"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else-if="!loading && parameterList.length === 0"
                    class="text-muted-foreground flex flex-col items-center gap-4 py-10 text-center"
                >
                    <div>Silakan isi parameter pemeriksaan terlebih dahulu sebelum mengisi nilai peserta.</div>
                    <Button variant="outline" @click="router.visit('/pemeriksaan')"> Ke Daftar Pemeriksaan </Button>
                </div>
                <div v-else class="text-muted-foreground py-10 text-center">Loading data...</div>
            </div>
        </div>
    </AppLayout>
</template>
