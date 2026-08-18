<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Berkas {
    id: number;
    jenis: string;
    label: string;
    url: string | null;
    file_nama: string | null;
}

interface Pendaftar {
    id: number;
    nomor_tes: string | null;
    nama: string;
    nik: string | null;
    nisn: string | null;
    jenis_kelamin: string;
    jenis_kelamin_label: string;
    tanggal_lahir: string;
    tahun_lahir: number;
    tempat_lahir: string | null;
    alamat: string | null;
    no_hp: string | null;
    email: string | null;
    sekolah: string | null;
    kelas_sekolah: string | null;
    tinggi_badan: number;
    berat_badan: number | null;
    posisi: string | null;
    vertical_jump: number | null;
    asal_kabupaten_bogor: boolean;
    bisa_dua_posisi: boolean;
    bisa_berenang: boolean;
    kuasai_poomsae: boolean;
    bersedia_pindah_domisili: boolean;
    setuju_perjanjian: boolean;
    cabor: string;
    map_label: string;
    status: string;
    status_label: string;
    alasan_tolak: string | null;
    skor_kecabangan_mentah: number | null;
    skor_kecabangan: number | null;
    skor_fisik: number | null;
    skor_akademik: number | null;
    skor_psikologi: number | null;
    psikologi_rekomendasi: boolean | null;
    kesehatan_layak: boolean | null;
    antropometri_layak: boolean | null;
    nilai_akhir: number | null;
    ranking: number | null;
    berkas: Berkas[];
    atlet: { id: number; nama: string } | null;
}

const props = defineProps<{
    pendaftar: Pendaftar;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Seleksi PPOPM', href: '/seleksi-ppopm' },
    { title: 'Pendaftar', href: '/seleksi-ppopm/pendaftar' },
    { title: props.pendaftar.nama, href: `/seleksi-ppopm/pendaftar/${props.pendaftar.id}` },
];

const rejectOpen = ref(false);
const rejectForm = useForm({ alasan_tolak: '' });
const tesForm = useForm({
    skor_kecabangan_mentah: props.pendaftar.skor_kecabangan_mentah as number | string | null,
    skor_fisik: props.pendaftar.skor_fisik as number | string | null,
    skor_akademik: props.pendaftar.skor_akademik as number | string | null,
    skor_psikologi: props.pendaftar.skor_psikologi as number | string | null,
    psikologi_rekomendasi: props.pendaftar.psikologi_rekomendasi === null ? '' : props.pendaftar.psikologi_rekomendasi ? '1' : '0',
    kesehatan_layak: props.pendaftar.kesehatan_layak === null ? '' : props.pendaftar.kesehatan_layak ? '1' : '0',
    antropometri_layak: props.pendaftar.antropometri_layak === null ? '' : props.pendaftar.antropometri_layak ? '1' : '0',
});

const canVerify = computed(() => ['submitted', 'ditolak_admin'].includes(props.pendaftar.status));
const canTes = computed(() => !!props.pendaftar.nomor_tes);
const canPromote = computed(() => props.pendaftar.status === 'lulus' && !props.pendaftar.atlet);

const verify = () => router.post(`/seleksi-ppopm/pendaftar/${props.pendaftar.id}/verify`);
const reject = () => {
    rejectForm.post(`/seleksi-ppopm/pendaftar/${props.pendaftar.id}/reject`, {
        onSuccess: () => {
            rejectOpen.value = false;
        },
    });
};
const saveTes = () => tesForm.post(`/seleksi-ppopm/pendaftar/${props.pendaftar.id}/tes`);
const promote = () => router.post(`/seleksi-ppopm/pendaftar/${props.pendaftar.id}/promote`);
</script>

<template>
    <Head :title="`Pendaftar ${pendaftar.nama}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-4 p-4 lg:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ pendaftar.nama }}</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ pendaftar.cabor }} · {{ pendaftar.map_label }} · {{ pendaftar.status_label }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="canVerify" @click="verify">Lulus administrasi</Button>
                    <Button v-if="canVerify" variant="destructive" @click="rejectOpen = true">Tolak</Button>
                    <Button v-if="canPromote" @click="promote">Angkat menjadi atlet</Button>
                    <Button variant="outline" @click="router.visit('/seleksi-ppopm/pendaftar')">Kembali</Button>
                </div>
            </div>

            <div v-if="pendaftar.alasan_tolak" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                {{ pendaftar.alasan_tolak }}
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>Data diri</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-2 gap-3 text-sm">
                        <p><span class="text-muted-foreground">Nomor tes:</span> {{ pendaftar.nomor_tes || '-' }}</p>
                        <p><span class="text-muted-foreground">JK:</span> {{ pendaftar.jenis_kelamin_label }}</p>
                        <p><span class="text-muted-foreground">Lahir:</span> {{ pendaftar.tempat_lahir || '-' }}, {{ pendaftar.tanggal_lahir }}</p>
                        <p><span class="text-muted-foreground">NIK:</span> {{ pendaftar.nik || '-' }}</p>
                        <p><span class="text-muted-foreground">Sekolah:</span> {{ pendaftar.sekolah }}</p>
                        <p><span class="text-muted-foreground">Kelas:</span> {{ pendaftar.kelas_sekolah || '-' }}</p>
                        <p><span class="text-muted-foreground">Tinggi:</span> {{ pendaftar.tinggi_badan }} cm</p>
                        <p><span class="text-muted-foreground">Posisi:</span> {{ pendaftar.posisi || '-' }}</p>
                        <p><span class="text-muted-foreground">Asal Kab. Bogor:</span> {{ pendaftar.asal_kabupaten_bogor ? 'Ya' : 'Tidak' }}</p>
                        <p><span class="text-muted-foreground">HP:</span> {{ pendaftar.no_hp || '-' }}</p>
                        <p class="col-span-2"><span class="text-muted-foreground">Alamat:</span> {{ pendaftar.alamat || '-' }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Berkas</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div v-for="file in pendaftar.berkas" :key="file.id" class="flex items-center justify-between gap-2">
                            <span>{{ file.label }}</span>
                            <a v-if="file.url" :href="file.url" target="_blank" class="text-primary underline">Lihat</a>
                        </div>
                        <p v-if="!pendaftar.berkas.length" class="text-muted-foreground">Belum ada berkas.</p>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="canTes">
                <CardHeader>
                    <CardTitle>Input tes seleksi umum</CardTitle>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-4 md:grid-cols-2" @submit.prevent="saveTes">
                        <div class="grid gap-2">
                            <Label>Kecabangan (skala 1–5)</Label>
                            <Input v-model="tesForm.skor_kecabangan_mentah" type="number" min="1" max="5" step="0.01" />
                            <p class="text-muted-foreground text-xs">
                                Terstandar: {{ pendaftar.skor_kecabangan ?? '-' }} (ambang 70, menggugurkan)
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label>Parameter fisik (0–100)</Label>
                            <Input v-model="tesForm.skor_fisik" type="number" min="0" max="100" step="0.01" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Potensi akademik (0–100)</Label>
                            <Input v-model="tesForm.skor_akademik" type="number" min="0" max="100" step="0.01" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Psikologi (0–100)</Label>
                            <Input v-model="tesForm.skor_psikologi" type="number" min="0" max="100" step="0.01" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Rekomendasi psikologi</Label>
                            <select v-model="tesForm.psikologi_rekomendasi" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                <option value="">Belum diisi</option>
                                <option value="1">Direkomendasikan</option>
                                <option value="0">Tidak direkomendasikan</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>Kesehatan (menggugurkan)</Label>
                            <select v-model="tesForm.kesehatan_layak" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                <option value="">Belum diisi</option>
                                <option value="1">Layak</option>
                                <option value="0">Tidak layak</option>
                            </select>
                        </div>
                        <div class="grid gap-2">
                            <Label>Antropometri (menggugurkan)</Label>
                            <select v-model="tesForm.antropometri_layak" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                <option value="">Belum diisi</option>
                                <option value="1">Memenuhi</option>
                                <option value="0">Tidak memenuhi</option>
                            </select>
                        </div>
                        <div class="grid gap-2 self-end">
                            <p class="text-sm">Nilai akhir: <strong>{{ pendaftar.nilai_akhir ?? '-' }}</strong> · Ranking: {{ pendaftar.ranking ?? '-' }}</p>
                            <Button type="submit" :disabled="tesForm.processing">Simpan nilai tes</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <p v-if="pendaftar.atlet" class="text-sm">
                Sudah diangkat menjadi atlet:
                <a :href="`/atlet/${pendaftar.atlet.id}`" class="text-primary underline">{{ pendaftar.atlet.nama }}</a>
            </p>

            <div v-if="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <Card class="w-full max-w-md">
                    <CardHeader><CardTitle>Tolak administrasi</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <Label>Alasan</Label>
                        <textarea v-model="rejectForm.alasan_tolak" class="border-input min-h-24 w-full rounded-md border p-2 text-sm" />
                        <div class="flex justify-end gap-2">
                            <Button variant="outline" @click="rejectOpen = false">Batal</Button>
                            <Button variant="destructive" :disabled="rejectForm.processing" @click="reject">Tolak</Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
