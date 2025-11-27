<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const atlet = computed(() => page.props.atlet || {}) as any;
const pemeriksaan = computed(() => page.props.pemeriksaan || {}) as any;
const parameters = computed(() => page.props.parameters || []);

const breadcrumbs = [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Riwayat Pemeriksaan', href: `/atlet/${atlet.value.id}/riwayat-pemeriksaan` },
    { title: 'Detail Parameter', href: `/atlet/${atlet.value.id}/riwayat-pemeriksaan/${pemeriksaan.value.id}/parameter` },
];

const getTrendColor = (trend: string) => {
    switch (trend) {
        case 'stabil':
            return 'bg-green-100 text-green-800';
        case 'kenaikan':
            return 'bg-yellow-100 text-yellow-800';
        case 'penurunan':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getTrendLabel = (trend: string) => {
    switch (trend) {
        case 'stabil':
            return 'Stabil';
        case 'kenaikan':
            return 'Kenaikan';
        case 'penurunan':
            return 'Penurunan';
        default:
            return 'Tidak Diketahui';
    }
};
</script>

<template>
    <Head title="Detail Parameter Pemeriksaan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen space-y-4 bg-gray-100 p-4 dark:bg-neutral-950">
            <!-- Header -->
            <div class="ml-1 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">Detail Parameter Pemeriksaan</h1>
                        <p class="text-muted-foreground">Riwayat parameter pemeriksaan atlet</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Informasi Atlet -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Atlet</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Nama:</span>
                                    <span class="text-sm font-medium">{{ atlet.nama }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">NIK:</span>
                                    <span class="text-sm font-medium">{{ atlet.nik }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Jenis Kelamin:</span>
                                    <span class="text-sm font-medium">{{ atlet.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Jumlah Parameter:</span>
                                    <Badge variant="secondary">{{ parameters.length }}</Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Informasi Pemeriksaan -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Pemeriksaan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Pemeriksaan:</span>
                                    <span class="text-sm font-medium">{{ pemeriksaan.nama_pemeriksaan }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Tanggal:</span>
                                    <span class="text-sm font-medium">
                                        {{
                                            pemeriksaan.tanggal_pemeriksaan
                                                ? new Date(pemeriksaan.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                                                      day: 'numeric',
                                                      month: 'long',
                                                      year: 'numeric',
                                                  })
                                                : '-'
                                        }}
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Tenaga Pendukung:</span>
                                    <span class="text-sm font-medium">{{ pemeriksaan.tenaga_pendukung }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-medium">Status:</span>
                                    <Badge
                                        :variant="
                                            pemeriksaan.status === 'selesai'
                                                ? 'success'
                                                : pemeriksaan.status === 'sebagian'
                                                  ? 'warning'
                                                  : 'destructive'
                                        "
                                    >
                                        {{ pemeriksaan.status }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabel Parameter -->
            <Card>
                <CardHeader>
                    <CardTitle>Parameter Pemeriksaan</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="border-border w-full border-collapse border">
                            <thead>
                                <tr class="bg-muted/50">
                                    <th class="border-border border px-4 py-2 text-left font-medium">No</th>
                                    <th class="border-border border px-4 py-2 text-left font-medium">Nama Parameter</th>
                                    <th class="border-border border px-4 py-2 text-left font-medium">Nilai</th>
                                    <th class="border-border border px-4 py-2 text-left font-medium">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(parameter, index) in parameters" :key="parameter.id" class="hover:bg-muted/30">
                                    <td class="border-border border px-4 py-2">{{ index + 1 }}</td>
                                    <td class="border-border border px-4 py-2 font-medium">{{ parameter.nama_parameter }}</td>
                                    <td class="border-border border px-4 py-2">{{ parameter.nilai || '-' }}</td>
                                    <td class="border-border border px-4 py-2">
                                        <Badge :class="getTrendColor(parameter.trend)">
                                            {{ getTrendLabel(parameter.trend) }}
                                        </Badge>
                                    </td>
                                </tr>
                                <tr v-if="parameters.length === 0">
                                    <td colspan="4" class="border-border text-muted-foreground border px-4 py-8 text-center">
                                        Tidak ada data parameter
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
