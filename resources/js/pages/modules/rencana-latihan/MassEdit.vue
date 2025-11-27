<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const props = defineProps<{
    program_id: number;
    program_latihan: {
        nama_program: string;
        cabor_nama: string;
        cabor_kategori_nama: string;
    };
    rencana_latihan_list: any[];
    target_latihan: any[];
}>();

const { toast } = useToast();

const tableState = ref<any[]>([]);
const loading = ref(false);

const trendOptions = [
    { value: 'naik', label: 'Naik' },
    { value: 'stabil', label: 'Stabil' },
    { value: 'turun', label: 'Turun' },
];

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rencana Latihan', href: `/program-latihan/${props.program_id}/rencana-latihan` },
    { title: 'Kelola Target Kelompok', href: '#' },
];

onMounted(async () => {
    try {
        // Initialize table state untuk semua rencana latihan
        tableState.value = props.rencana_latihan_list.map((rencana) => {
            return {
                rencana_id: rencana.id,
                tanggal: rencana.tanggal,
                materi: rencana.materi,
                lokasi_latihan: rencana.lokasi_latihan,
                jumlah_atlet: rencana.jumlah_atlet,
                jumlah_pelatih: rencana.jumlah_pelatih,
                jumlah_tenaga_pendukung: rencana.jumlah_tenaga_pendukung,
                targets: rencana.target_latihan.map((target: any) => {
                    return {
                        target_latihan_id: target.id,
                        nilai: '',
                        trend: 'stabil',
                    };
                }),
            };
        });

        // Fetch existing data untuk semua rencana latihan
        for (let i = 0; i < tableState.value.length; i++) {
            const rencana = tableState.value[i];
            try {
                const response = await axios.get(`/api/rencana-latihan/${rencana.rencana_id}/target-kelompok-mapping`);
                const existingData = response.data || {};

                rencana.targets.forEach((target: any) => {
                    const existing = existingData[target.target_latihan_id] || {};
                    target.nilai = existing.nilai || '';
                    target.trend = existing.trend || 'stabil';
                });
            } catch (error) {
                console.error(`Error loading data for rencana ${rencana.rencana_id}:`, error);
            }
        }
    } catch (error) {
        console.error('Error loading existing data:', error);
    }
});

const handleSave = async () => {
    try {
        loading.value = true;

        // Flatten data untuk setiap rencana latihan dan target
        const flattenedData: any[] = [];

        tableState.value.forEach((rencana) => {
            rencana.targets.forEach((target: any) => {
                flattenedData.push({
                    rencana_latihan_id: rencana.rencana_id,
                    target_latihan_id: target.target_latihan_id,
                    nilai: target.nilai,
                    trend: target.trend,
                });
            });
        });

        const payload = {
            data: flattenedData,
        };

        const response = await axios.post(`/program-latihan/${props.program_id}/rencana-latihan/kelola-target-kelompok/bulk-update`, payload);

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

const getUniqueTargets = () => {
    // Ambil semua target unik dari semua rencana latihan
    const allTargets = new Map();
    props.rencana_latihan_list.forEach((rencana) => {
        rencana.target_latihan.forEach((target: any) => {
            if (!allTargets.has(target.id)) {
                allTargets.set(target.id, target);
            }
        });
    });
    return Array.from(allTargets.values());
};

const getTargetNilai = (rencana: any, targetId: number) => {
    const target = rencana.targets.find((t: any) => t.target_latihan_id === targetId);
    return target ? target.nilai : '';
};

const getTargetTrend = (rencana: any, targetId: number) => {
    const target = rencana.targets.find((t: any) => t.target_latihan_id === targetId);
    return target ? target.trend : 'stabil';
};

const updateTargetNilai = (rencana: any, targetId: number, value: string) => {
    const target = rencana.targets.find((t: any) => t.target_latihan_id === targetId);
    if (target) {
        target.nilai = value;
    }
};

const updateTargetTrend = (rencana: any, targetId: number, value: string) => {
    const target = rencana.targets.find((t: any) => t.target_latihan_id === targetId);
    if (target) {
        target.trend = value;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen w-full bg-gray-100 pt-4 dark:bg-neutral-950">
            <div class="mx-auto max-w-7xl px-4">
                <!-- Info Card -->
                <div class="bg-card mb-4 rounded-lg border p-4">
                    <h3 class="mb-2 text-lg font-semibold">Informasi Program Latihan</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Nama Program:</span>
                            <span class="text-sm font-medium">{{ props.program_latihan.nama_program }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                            <span class="text-sm font-medium">
                                {{ props.program_latihan.cabor_nama }} -
                                {{ props.program_latihan.cabor_kategori_nama }}
                            </span>
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
                <div class="text-foreground mb-2 text-lg font-semibold">Target Latihan Kelompok</div>

                <!-- Table -->
                <div v-if="tableState.length && getUniqueTargets().length" class="overflow-x-auto rounded-xl bg-white shadow dark:bg-neutral-900">
                    <table class="w-full min-w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-muted">
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap">Tanggal</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap">Materi</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap">Lokasi Latihan</th>
                                <th class="text-foreground border-b px-3 py-2 whitespace-nowrap">Peserta</th>
                                <template v-for="target in getUniqueTargets()" :key="'target-header-' + target.id">
                                    <th class="text-foreground border-b px-3 py-2 text-center whitespace-nowrap" :colspan="2">
                                        {{ target.deskripsi }}
                                        <div class="text-muted-foreground text-xs">Target: {{ getTargetValue(target) }}</div>
                                        <div class="text-muted-foreground text-xs">{{ target.satuan }}</div>
                                    </th>
                                </template>
                            </tr>
                            <tr class="bg-muted">
                                <th class="border-b px-2 py-1"></th>
                                <th class="border-b px-2 py-1"></th>
                                <th class="border-b px-2 py-1"></th>
                                <th class="border-b px-2 py-1"></th>
                                <template v-for="target in getUniqueTargets()" :key="'target-subheader-' + target.id">
                                    <th class="text-foreground border-b px-2 py-1 whitespace-nowrap">Nilai</th>
                                    <th class="text-foreground border-b px-2 py-1 whitespace-nowrap">Trend</th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="rencana in tableState"
                                :key="'rencana-row-' + rencana.rencana_id"
                                class="hover:bg-muted/40 border-t transition"
                            >
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">
                                    {{ new Date(rencana.tanggal).toLocaleDateString('id-ID') }}
                                </td>
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ rencana.materi }}</td>
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">{{ rencana.lokasi_latihan }}</td>
                                <td class="text-foreground border-b px-3 py-2 whitespace-nowrap">
                                    <BadgeGroup
                                        :badges="[
                                            {
                                                label: 'Atlet',
                                                value: rencana.jumlah_atlet || 0,
                                                colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                                            },
                                            {
                                                label: 'Pelatih',
                                                value: rencana.jumlah_pelatih || 0,
                                                colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                                            },
                                            {
                                                label: 'Tenaga Pendukung',
                                                value: rencana.jumlah_tenaga_pendukung || 0,
                                                colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                                            },
                                        ]"
                                    />
                                </td>

                                <template v-for="uniqueTarget in getUniqueTargets()" :key="'unique-target-' + uniqueTarget.id">
                                    <template v-if="rencana.targets.some((t: any) => t.target_latihan_id === uniqueTarget.id)">
                                        <td class="border-b px-2 py-1 text-center whitespace-nowrap">
                                            <div class="flex justify-center">
                                                <Input
                                                    type="text"
                                                    class="bg-background text-foreground border-border w-24 rounded border px-1 py-0.5 text-center"
                                                    :model-value="getTargetNilai(rencana, uniqueTarget.id)"
                                                    @update:model-value="(val: string) => updateTargetNilai(rencana, uniqueTarget.id, val)"
                                                    :placeholder="getTargetValue(uniqueTarget)"
                                                    style="text-align: center"
                                                />
                                            </div>
                                        </td>
                                        <td class="border-b px-2 py-1 text-center whitespace-nowrap">
                                            <div class="flex justify-center">
                                                <SimpleSelect
                                                    :model-value="getTargetTrend(rencana, uniqueTarget.id)"
                                                    @update:model-value="(val: string) => updateTargetTrend(rencana, uniqueTarget.id, val)"
                                                    :options="trendOptions"
                                                    placeholder="Pilih trend"
                                                />
                                            </div>
                                        </td>
                                    </template>
                                    <template v-else>
                                        <td class="bg-muted/20 border-b px-2 py-1 text-center whitespace-nowrap">
                                            <span class="text-muted-foreground text-xs">-</span>
                                        </td>
                                        <td class="bg-muted/20 border-b px-2 py-1 text-center whitespace-nowrap">
                                            <span class="text-muted-foreground text-xs">-</span>
                                        </td>
                                    </template>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div
                    v-else-if="!loading && props.rencana_latihan_list.length === 0"
                    class="text-muted-foreground flex flex-col items-center gap-4 py-10 text-center"
                >
                    <div>Program latihan ini belum memiliki rencana latihan.</div>
                    <Button variant="outline" @click="router.visit(`/program-latihan/${props.program_id}/rencana-latihan/create`)">
                        Buat Rencana Latihan
                    </Button>
                </div>
                <div
                    v-else-if="!loading && getUniqueTargets().length === 0"
                    class="text-muted-foreground flex flex-col items-center gap-4 py-10 text-center"
                >
                    <div>Program latihan ini belum memiliki target latihan kelompok.</div>
                    <Button variant="outline" @click="router.visit(`/program-latihan/${props.program_id}/target-latihan/kelompok`)">
                        Buat Target Kelompok
                    </Button>
                </div>
                <div v-else class="text-muted-foreground py-10 text-center">Loading data...</div>
            </div>
        </div>
    </AppLayout>
</template>
