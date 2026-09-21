<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { Calendar, ExternalLink, List, Users } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    caborId: number;
    caborNama: string;
}>();

interface ProgramItem {
    id: number;
    nama_program: string;
    cabor_kategori_nama?: string;
    periode_mulai: string;
    periode_selesai: string;
    periode_hitung?: string;
    tahap?: string;
}

const programs = ref<ProgramItem[]>([]);
const loading = ref(false);
const onlyActive = ref(false);

const fetchPrograms = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/program-latihan', {
            params: {
                cabor_id: props.caborId,
                per_page: 100,
                sort: 'periode_mulai',
                order: 'desc',
            },
        });
        programs.value = (response.data.data ?? []).map((item: any) => ({
            id: item.id,
            nama_program: item.nama_program,
            cabor_kategori_nama: item.cabor_kategori_nama ?? item.cabor_kategori?.nama,
            periode_mulai: item.periode_mulai,
            periode_selesai: item.periode_selesai,
            periode_hitung: item.periode_hitung,
            tahap: item.tahap,
        }));
    } catch {
        programs.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(fetchPrograms);
watch(() => props.caborId, fetchPrograms);

const today = () => new Date().toISOString().slice(0, 10);

const getProgramStatus = (program: ProgramItem) => {
    const current = today();
    if (program.periode_mulai <= current && program.periode_selesai >= current) {
        return { label: 'Aktif', variant: 'default' as const };
    }
    if (program.periode_selesai < current) {
        return { label: 'Selesai', variant: 'secondary' as const };
    }
    return { label: 'Akan Datang', variant: 'outline' as const };
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const activeCount = computed(() =>
    programs.value.filter((p) => getProgramStatus(p).label === 'Aktif').length
);

const filteredPrograms = computed(() => {
    if (!onlyActive.value) return programs.value;
    return programs.value.filter((p) => getProgramStatus(p).label === 'Aktif');
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <Calendar class="h-5 w-5" />
                    Program Latihan — {{ caborNama }}
                </h3>
                <p class="text-sm text-muted-foreground mt-1">
                    {{ programs.length }} program · {{ activeCount }} sedang berjalan
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <Checkbox id="only-active" :checked="onlyActive" @update:checked="(val: boolean) => (onlyActive = val)" />
                    <Label for="only-active" class="cursor-pointer text-sm">Hanya program aktif</Label>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <a :href="`/program-latihan?cabor_id=${caborId}`">
                        <List class="h-4 w-4 mr-1.5" />
                        Lihat Semua di Index
                    </a>
                </Button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-8 text-muted-foreground">Memuat data...</div>

        <div v-else-if="filteredPrograms.length === 0" class="text-center py-12 text-muted-foreground">
            {{ onlyActive ? 'Tidak ada program latihan aktif untuk cabor ini' : 'Belum ada program latihan untuk cabor ini' }}
        </div>

        <div v-else class="grid gap-4">
            <Card v-for="program in filteredPrograms" :key="program.id" class="hover:shadow-md transition-shadow">
                <CardHeader class="pb-3">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="text-base">{{ program.nama_program }}</CardTitle>
                            <CardDescription>{{ program.cabor_kategori_nama || '-' }}</CardDescription>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <Badge :variant="getProgramStatus(program).variant">{{ getProgramStatus(program).label }}</Badge>
                            <Badge v-if="program.tahap" variant="secondary">{{ program.tahap }}</Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        {{ formatDate(program.periode_mulai) }} — {{ formatDate(program.periode_selesai) }}
                        <span v-if="program.periode_hitung"> · {{ program.periode_hitung }}</span>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" as-child>
                            <a :href="`/program-latihan/${program.id}/rekap-absen`">
                                <Users class="h-4 w-4 mr-1.5" />
                                Rekap Absen
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" as-child>
                            <a :href="`/program-latihan/${program.id}/rekap-absen/detail?tab=monitoring-absen`">
                                <ExternalLink class="h-4 w-4 mr-1.5" />
                                Monitoring Foto Absen
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" as-child>
                            <a :href="`/program-latihan/${program.id}/rekap-absen/detail?tab=ringkasan-atlet`">
                                <ExternalLink class="h-4 w-4 mr-1.5" />
                                Ringkasan Atlet
                            </a>
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
