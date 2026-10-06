<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Row {
    id: number;
    nama: string;
    cabor: string;
    jenis_kelamin_label: string;
    posisi: string | null;
    asal_kabupaten_bogor: boolean;
    status: string;
    status_label: string;
    nilai_akhir: number | null;
    ranking: number | null;
    atlet_id: number | null;
}

const props = defineProps<{
    periode: { nama: string } | null;
    rows: Row[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Seleksi PPOPM', href: '/seleksi-ppopm' },
    { title: 'Pleno', href: '/seleksi-ppopm/pleno' },
];

const grouped = computed(() => {
    const map = new Map<string, Row[]>();
    for (const row of props.rows) {
        const key = row.cabor || 'Lainnya';
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(row);
    }
    return Array.from(map.entries());
});

const runPleno = () => router.post('/seleksi-ppopm/pleno');
</script>

<template>
    <Head title="Pleno Seleksi PPOPM" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4 lg:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Pleno penetapan</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ periode?.nama }} · Rumus: kecabangan 40% + fisik 25% + akademik 10% + psikologi 25%
                    </p>
                </div>
                <Button @click="runPleno">Hitung ranking & kelulusan</Button>
            </div>

            <Card v-if="!rows.length">
                <CardContent class="text-muted-foreground py-8 text-sm">
                    Belum ada nilai akhir. Isi tes pendaftar yang lulus administrasi, lalu jalankan pleno.
                </CardContent>
            </Card>

            <Card v-for="[cabor, items] in grouped" :key="cabor">
                <CardHeader>
                    <CardTitle>{{ cabor }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Rank</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>JK</TableHead>
                                <TableHead>Posisi</TableHead>
                                <TableHead>Asal</TableHead>
                                <TableHead>Nilai</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in items" :key="row.id">
                                <TableCell>{{ row.ranking ?? '-' }}</TableCell>
                                <TableCell>{{ row.nama }}</TableCell>
                                <TableCell>{{ row.jenis_kelamin_label }}</TableCell>
                                <TableCell>{{ row.posisi || '-' }}</TableCell>
                                <TableCell>{{ row.asal_kabupaten_bogor ? 'Kab. Bogor' : 'Luar daerah' }}</TableCell>
                                <TableCell>{{ row.nilai_akhir ?? '-' }}</TableCell>
                                <TableCell>{{ row.status_label }}</TableCell>
                                <TableCell>
                                    <Button variant="outline" size="sm" @click="router.visit(`/seleksi-ppopm/pendaftar/${row.id}`)">
                                        Detail
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
