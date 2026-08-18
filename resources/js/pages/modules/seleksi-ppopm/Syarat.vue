<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Syarat {
    id: number;
    kode: string;
    nama_cabor: string;
    kelompok_map: string;
    tahun_lahir_min: number;
    tahun_lahir_max: number;
    tinggi_min_putra: number | null;
    tinggi_min_putri: number | null;
    tinggi_per_posisi: Record<string, number> | null;
    posisi: string[] | null;
    kuota_putra: number;
    kuota_putri: number;
    kuota_detail: Record<string, number> | null;
    jenis_kelamin_diizinkan: string[];
    wajib_piagam: boolean;
    wajib_berenang: boolean;
    wajib_dua_posisi: boolean;
    prioritas_tinggi: number | null;
    toleransi_tinggi: { min: number; max: number; vertical_jump: number } | null;
    syarat_tambahan: string | null;
}

const props = defineProps<{
    periode: { nama: string } | null;
    syarat: Syarat[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Seleksi PPOPM', href: '/seleksi-ppopm' },
    { title: 'Syarat cabor', href: '/seleksi-ppopm/syarat' },
];

const mapColor: Record<string, string> = {
    permainan: 'bg-red-600',
    bela_diri: 'bg-blue-600',
    terukur: 'bg-green-600',
};

const mapLabel: Record<string, string> = {
    permainan: 'Map merah',
    bela_diri: 'Map biru',
    terukur: 'Map hijau',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Syarat Cabor Seleksi PPOPM" />

        <div class="space-y-4 p-4 lg:p-6">
            <div>
                <h1 class="text-xl font-semibold">Persyaratan khusus per cabor</h1>
                <p class="text-muted-foreground text-sm">{{ periode?.nama || 'Periode seleksi' }}</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card v-for="item in props.syarat" :key="item.id">
                    <CardHeader class="flex flex-row items-start justify-between gap-2 space-y-0">
                        <CardTitle>{{ item.nama_cabor }}</CardTitle>
                        <Badge :class="mapColor[item.kelompok_map]">{{ mapLabel[item.kelompok_map] }}</Badge>
                    </CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p>Tahun lahir: {{ item.tahun_lahir_min }}–{{ item.tahun_lahir_max }}</p>
                        <p>
                            Tinggi min: putra {{ item.tinggi_min_putra ?? '-' }} cm / putri {{ item.tinggi_min_putri ?? '-' }} cm
                        </p>
                        <p v-if="item.tinggi_per_posisi">
                            Tinggi per posisi:
                            {{
                                Object.entries(item.tinggi_per_posisi)
                                    .map(([posisi, tinggi]) => `${posisi} ${tinggi} cm`)
                                    .join(', ')
                            }}
                        </p>
                        <p v-if="item.posisi?.length">Posisi: {{ item.posisi.join(', ') }}</p>
                        <p>Kuota: putra {{ item.kuota_putra }} / putri {{ item.kuota_putri }}</p>
                        <p>Jenis kelamin: {{ (item.jenis_kelamin_diizinkan || []).join(', ') }}</p>
                        <p v-if="item.wajib_piagam">Wajib piagam/sertifikat kejuaraan</p>
                        <p v-if="item.wajib_berenang">Wajib bisa berenang</p>
                        <p v-if="item.wajib_dua_posisi">Wajib mampu bermain minimal 2 posisi</p>
                        <p v-if="item.prioritas_tinggi">Prioritas tinggi ≥ {{ item.prioritas_tinggi }} cm</p>
                        <p v-if="item.toleransi_tinggi">
                            Toleransi tinggi {{ item.toleransi_tinggi.min }}–{{ item.toleransi_tinggi.max }} cm jika vertical jump
                            {{ item.toleransi_tinggi.vertical_jump }} cm
                        </p>
                        <p class="text-muted-foreground">{{ item.syarat_tambahan }}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
