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
    program_id: number;
    rencana_latihan: {
        id: number;
        tanggal: string;
        materi: string;
        lokasi_latihan: string;
        program_latihan: {
            nama_program: string;
            cabor_nama: string;
            cabor_kategori_nama: string;
        };
    };
    jenis_peserta: string;
    target_latihan: any[];
    peserta_list: any[];
}>();

const { toast } = useToast();

const tableState = ref<any[]>([]);
const loading = ref(false);

const trendOptions = [
    { value: 'naik', label: 'Naik' },
    { value: 'stabil', label: 'Stabil' },
    { value: 'turun', label: 'Turun' },
];

const jenisLabel: Record<string, string> = {
    atlet: 'Atlet',
    pelatih: 'Pelatih',
    'tenaga-pendukung': 'Tenaga Pendukung',
};

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${props.program_id}/rencana-latihan` },
    { title: `Kelola Pemetaan (${jenisLabel[props.jenis_peserta] || props.jenis_peserta})`, href: '#' },
];

onMounted(async () => {
    try {
        // Fetch existing data
        const response = await axios.get(`/api/rencana-latihan/${props.rencana_latihan.id}/target-mapping?jenis_peserta=${props.jenis_peserta}`);
        const existingData = response.data || {};

        // Initialize table state
        tableState.value = props.peserta_list.map((peserta) => {
            return {
                peserta_id: peserta.id,
                peserta: {
                    nama: peserta.nama,
                    jenis_kelamin: peserta.jenis_kelamin,
                    usia: peserta.usia,
                    posisi: peserta.posisi || peserta.jenis_pelatih || peserta.jenis_tenaga_pendukung || '-',
                },
                targets: props.target_latihan.map((target) => {
                    const existing = existingData[peserta.id] && existingData[peserta.id][target.id] ? existingData[peserta.id][target.id] : {};
                    return {
                        target_latihan_id: target.id,
                        nilai: existing.nilai || '',
                        trend: existing.trend || 'stabil',
                    };
                }),
            };
        });
    } catch (error) {
        console.error('Error loading existing data:', error);
        // Fallback to empty data
        tableState.value = props.peserta_list.map((peserta) => {
            return {
                peserta_id: peserta.id,
                peserta: {
                    nama: peserta.nama,
                    jenis_kelamin: peserta.jenis_kelamin,
                    usia: peserta.usia,
                    posisi: peserta.posisi || peserta.jenis_pelatih || peserta.jenis_tenaga_pendukung || '-',
                },
                targets: props.target_latihan.map((target) => {
                    return {
                        target_latihan_id: target.id,
                        nilai: '',
                        trend: 'stabil',
                    };
                }),
            };
        });
    }
});

const handleSave = async () => {
    try {
        loading.value = true;

        // Flatten data untuk setiap peserta dan target
        const flattenedData: any[] = [];

        tableState.value.forEach((row) => {
            row.targets.forEach((target: any) => {
                flattenedData.push({
                    peserta_id: row.peserta_id,
                    target_latihan_id: target.target_latihan_id,
                    nilai: target.nilai,
                    trend: target.trend,
                });
            });
        });

        const payload = {
            data: flattenedData,
        };

        const response = await axios.post(
            `/program-latihan/${props.program_id}/rencana-latihan/${props.rencana_latihan.id}/kelola/${props.jenis_peserta}/bulk-update`,
            payload,
        );

        if (response.data?.success) {
            toast({ title: response.data?.message || 'Data berhasil disimpan', variant: 'success' });
        }
    } catch (error: any) {
        const msg = error.response?.data?.message || 'Gagal menyimpan data, pastikan semua nilai sudah diisi!';
        toast({ title: msg, variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

const getTargetValue = (target: any) => {
    return target.nilai_target || '-';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen w-full bg-gray-100 pt-4 dark:bg-neutral-950">
            <div class="mx-auto max-w-7xl px-4">
                <!-- Info Card -->
                <div class="bg-card mb-4 rounded-lg border p-4">
                    <h3 class="mb-2 text-lg font-semibold">Informasi Rencana Latihan</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Program:</span>
                                <span class="text-sm font-medium">{{ props.rencana_latihan.program_latihan.nama_program }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                                <span class="text-sm font-medium">
                                    {{ props.rencana_latihan.program_latihan.cabor_nama }} -
                                    {{ props.rencana_latihan.program_latihan.cabor_kategori_nama }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Tanggal:</span>
                                <span class="text-sm font-medium">
                                    {{ new Date(props.rencana_latihan.tanggal).toLocaleDateString('id-ID') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Materi:</span>
                                <span class="text-sm font-medium">{{ props.rencana_latihan.materi }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground text-sm font-medium">Lokasi:</span>
                                <span class="text-sm font-medium">{{ props.rencana_latihan.lokasi_latihan }}</span>
                            </div>
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
                <div class="text-foreground mb-2 text-lg font-semibold">Peserta {{ jenisLabel[props.jenis_peserta] || props.jenis_peserta }}</div>

                <!-- Table -->
                <div v-if="tableState.length && props.target_latihan.length" class="overflow-x-auto rounded-xl bg-white shadow dark:bg-neutral-900">
                    <table class="w-full min-w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-muted">
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Nama</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Jenis Kelamin</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">Usia</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap" rowspan="2">
                                    {{
                                        props.jenis_peserta === 'atlet'
                                            ? 'Posisi'
                                            : props.jenis_peserta === 'pelatih'
                                              ? 'Jenis Pelatih'
                                              : 'Jenis Tenaga Pendukung'
                                    }}
                                </th>
                                <template v-for="target in props.target_latihan" :key="'target-header-' + target.id">
                                    <th class="text-foreground border-b px-3 py-2 text-center whitespace-nowrap" :colspan="2">
                                        {{ target.deskripsi }}
                                        <div class="text-muted-foreground text-xs">Target: {{ getTargetValue(target) }}</div>
                                        <div class="text-muted-foreground text-xs">{{ target.satuan }}</div>
                                    </th>
                                </template>
                            </tr>
                            <tr class="bg-muted">
                                <template v-for="target in props.target_latihan" :key="'target-subheader-' + target.id">
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
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ row.peserta.posisi }}</td>

                                <template v-for="(target, targetIdx) in row.targets" :key="'target-' + target.target_latihan_id">
                                    <td class="border-b px-2 py-1 whitespace-nowrap">
                                        <Input
                                            type="text"
                                            class="bg-background text-foreground w-24 rounded border px-1 py-0.5 text-right"
                                            v-model="tableState[rowIdx].targets[targetIdx].nilai"
                                            :placeholder="getTargetValue(props.target_latihan[targetIdx])"
                                        />
                                    </td>
                                    <td class="border-b px-2 py-1 whitespace-nowrap">
                                        <SimpleSelect
                                            :model-value="tableState[rowIdx].targets[targetIdx].trend"
                                            @update:modelValue="(val: string) => (tableState[rowIdx].targets[targetIdx].trend = val)"
                                            :options="trendOptions"
                                            placeholder="Pilih trend"
                                        />
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else-if="!loading && props.target_latihan.length === 0"
                    class="text-muted-foreground flex flex-col items-center gap-4 py-10 text-center"
                >
                    <div>Rencana latihan ini belum memiliki target latihan individu yang dipilih.</div>
                    <div class="text-sm">Silakan pilih target latihan individu terlebih dahulu di halaman edit rencana latihan.</div>
                    <Button
                        variant="outline"
                        @click="router.visit(`/program-latihan/${props.program_id}/rencana-latihan/${props.rencana_latihan.id}/edit`)"
                    >
                        Edit Rencana Latihan
                    </Button>
                </div>
                <div v-else class="text-muted-foreground py-10 text-center">Loading data...</div>
            </div>
        </div>
    </AppLayout>
</template>
