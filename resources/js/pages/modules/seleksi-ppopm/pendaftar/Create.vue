<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeaderForm from '@/pages/modules/base-page/HeaderForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface CaborSyarat {
    id: number;
    kode: string;
    nama_cabor: string;
    map_label: string;
    tahun_lahir_min: number;
    tahun_lahir_max: number;
    tinggi_min_putra: number | null;
    tinggi_min_putri: number | null;
    tinggi_per_posisi: Record<string, number> | null;
    posisi: string[];
    kuota_putra: number;
    kuota_putri: number;
    jenis_kelamin_diizinkan: string[];
    wajib_piagam: boolean;
    wajib_berenang: boolean;
    wajib_dua_posisi: boolean;
    prioritas_tinggi: number | null;
    toleransi_tinggi: { min: number; max: number; vertical_jump: number } | null;
    syarat_tambahan: string | null;
}

const props = defineProps<{
    periode: { id: number; nama: string } | null;
    pendaftaranBuka: boolean;
    caborSyarat: CaborSyarat[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Seleksi PPOPM', href: '/seleksi-ppopm' },
    { title: 'Pendaftar', href: '/seleksi-ppopm/pendaftar' },
    { title: 'Daftar', href: '/seleksi-ppopm/pendaftar/create' },
];

const form = useForm({
    periode_id: props.periode?.id ?? '',
    cabor_syarat_id: '',
    nama: '',
    nik: '',
    nisn: '',
    jenis_kelamin: 'P',
    tempat_lahir: '',
    tanggal_lahir: '',
    alamat: '',
    no_hp: '',
    email: '',
    sekolah: '',
    kelas_sekolah: '',
    asal_kabupaten_bogor: true,
    tinggi_badan: '',
    berat_badan: '',
    posisi: '',
    nomor_kelas: '',
    vertical_jump: '',
    bisa_dua_posisi: false,
    bisa_berenang: false,
    kuasai_poomsae: false,
    bersedia_pindah_domisili: false,
    setuju_perjanjian: false,
    berkas_kk: null as File | null,
    berkas_akta: null as File | null,
    berkas_bpjs: null as File | null,
    berkas_ijazah: null as File | null,
    berkas_surat_sehat: null as File | null,
    berkas_rekomendasi: null as File | null,
    berkas_piagam: null as File | null,
});

const selectedSyarat = computed(() => props.caborSyarat.find((item) => String(item.id) === String(form.cabor_syarat_id)) || null);

const fieldClass = 'grid gap-2';

const onFile = (field: keyof typeof form, event: Event) => {
    const input = event.target as HTMLInputElement;
    form[field] = input.files?.[0] ?? null;
};

const submit = () => {
    form.post('/seleksi-ppopm/pendaftar', { forceFormData: true });
};
</script>

<template>
    <Head title="Pendaftaran Seleksi PPOPM" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="page-surface space-y-4 p-4">
            <Card>
                <HeaderForm title="Pendaftaran Calon Atlet PPOPM" back-url="/seleksi-ppopm/pendaftar" />
                <CardContent>
                    <p v-if="!pendaftaranBuka" class="text-destructive mb-4 text-sm">Pendaftaran sedang ditutup.</p>

                    <form class="grid gap-6" @submit.prevent="submit">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div :class="fieldClass">
                                <Label>Cabor</Label>
                                <select v-model="form.cabor_syarat_id" class="border-input bg-background h-9 rounded-md border px-3 text-sm" required>
                                    <option value="" disabled>Pilih cabor</option>
                                    <option v-for="item in caborSyarat" :key="item.id" :value="item.id">{{ item.nama_cabor }}</option>
                                </select>
                                <p v-if="form.errors.cabor_syarat_id" class="text-destructive text-xs">{{ form.errors.cabor_syarat_id }}</p>
                            </div>
                            <div v-if="selectedSyarat" class="bg-muted/40 rounded-md border p-3 text-sm">
                                <p class="font-medium">{{ selectedSyarat.nama_cabor }} · {{ selectedSyarat.map_label }}</p>
                                <p>Tahun lahir {{ selectedSyarat.tahun_lahir_min }}–{{ selectedSyarat.tahun_lahir_max }}</p>
                                <p>
                                    Tinggi min putra {{ selectedSyarat.tinggi_min_putra ?? '-' }} / putri
                                    {{ selectedSyarat.tinggi_min_putri ?? '-' }} cm
                                </p>
                                <p>{{ selectedSyarat.syarat_tambahan }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div :class="fieldClass">
                                <Label>Nama lengkap</Label>
                                <Input v-model="form.nama" required />
                                <p v-if="form.errors.nama" class="text-destructive text-xs">{{ form.errors.nama }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>Jenis kelamin</Label>
                                <select v-model="form.jenis_kelamin" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                    <option value="L">Putra</option>
                                    <option value="P">Putri</option>
                                </select>
                                <p v-if="form.errors.jenis_kelamin" class="text-destructive text-xs">{{ form.errors.jenis_kelamin }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>NIK</Label>
                                <Input v-model="form.nik" />
                            </div>
                            <div :class="fieldClass">
                                <Label>NISN</Label>
                                <Input v-model="form.nisn" />
                            </div>
                            <div :class="fieldClass">
                                <Label>Tempat lahir</Label>
                                <Input v-model="form.tempat_lahir" />
                            </div>
                            <div :class="fieldClass">
                                <Label>Tanggal lahir</Label>
                                <Input v-model="form.tanggal_lahir" type="date" required />
                                <p v-if="form.errors.tanggal_lahir" class="text-destructive text-xs">{{ form.errors.tanggal_lahir }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>Sekolah (SMP/MTs)</Label>
                                <Input v-model="form.sekolah" required />
                                <p v-if="form.errors.sekolah" class="text-destructive text-xs">{{ form.errors.sekolah }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>Kelas</Label>
                                <Input v-model="form.kelas_sekolah" />
                            </div>
                            <div :class="fieldClass">
                                <Label>Tinggi badan (cm)</Label>
                                <Input v-model="form.tinggi_badan" type="number" step="0.1" required />
                                <p v-if="form.errors.tinggi_badan" class="text-destructive text-xs">{{ form.errors.tinggi_badan }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>Berat badan (kg)</Label>
                                <Input v-model="form.berat_badan" type="number" step="0.1" />
                            </div>
                            <div v-if="selectedSyarat?.posisi?.length" :class="fieldClass">
                                <Label>Posisi / nomor</Label>
                                <select v-model="form.posisi" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                                    <option value="">Pilih posisi</option>
                                    <option v-for="posisi in selectedSyarat.posisi" :key="posisi" :value="posisi">{{ posisi }}</option>
                                </select>
                                <p v-if="form.errors.posisi" class="text-destructive text-xs">{{ form.errors.posisi }}</p>
                            </div>
                            <div v-if="selectedSyarat?.toleransi_tinggi" :class="fieldClass">
                                <Label>Vertical jump (cm)</Label>
                                <Input v-model="form.vertical_jump" type="number" step="0.1" />
                            </div>
                            <div :class="fieldClass">
                                <Label>No. HP</Label>
                                <Input v-model="form.no_hp" />
                            </div>
                            <div :class="fieldClass">
                                <Label>Email</Label>
                                <Input v-model="form.email" type="email" />
                            </div>
                        </div>

                        <div :class="fieldClass">
                            <Label>Alamat</Label>
                            <textarea v-model="form.alamat" class="border-input min-h-20 rounded-md border p-2 text-sm" />
                        </div>

                        <div class="grid gap-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.asal_kabupaten_bogor" type="checkbox" class="size-4" />
                                Asal Kabupaten Bogor
                            </label>
                            <label v-if="!form.asal_kabupaten_bogor" class="flex items-center gap-2 text-sm">
                                <input v-model="form.bersedia_pindah_domisili" type="checkbox" class="size-4" />
                                Bersedia pindah sekolah dan domisili ke Kabupaten Bogor
                            </label>
                            <p v-if="form.errors.bersedia_pindah_domisili" class="text-destructive text-xs">{{ form.errors.bersedia_pindah_domisili }}</p>
                            <label v-if="selectedSyarat?.wajib_dua_posisi" class="flex items-center gap-2 text-sm">
                                <input v-model="form.bisa_dua_posisi" type="checkbox" class="size-4" />
                                Mampu bermain minimal 2 posisi
                            </label>
                            <p v-if="form.errors.bisa_dua_posisi" class="text-destructive text-xs">{{ form.errors.bisa_dua_posisi }}</p>
                            <label v-if="selectedSyarat?.wajib_berenang" class="flex items-center gap-2 text-sm">
                                <input v-model="form.bisa_berenang" type="checkbox" class="size-4" />
                                Bisa berenang
                            </label>
                            <p v-if="form.errors.bisa_berenang" class="text-destructive text-xs">{{ form.errors.bisa_berenang }}</p>
                            <label v-if="selectedSyarat?.kode === 'taekwondo-poomsae'" class="flex items-center gap-2 text-sm">
                                <input v-model="form.kuasai_poomsae" type="checkbox" class="size-4" />
                                Menguasai Taeguk 4 sampai Pyongwon
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input v-model="form.setuju_perjanjian" type="checkbox" class="size-4" />
                                Menyetujui perjanjian peraturan UPT PPOPM Dispora Kabupaten Bogor
                            </label>
                            <p v-if="form.errors.setuju_perjanjian" class="text-destructive text-xs">{{ form.errors.setuju_perjanjian }}</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div v-for="field in [
                                { name: 'berkas_kk', label: 'Kartu Keluarga' },
                                { name: 'berkas_akta', label: 'Akta kelahiran' },
                                { name: 'berkas_bpjs', label: 'BPJS Kesehatan aktif' },
                                { name: 'berkas_ijazah', label: 'Ijazah terakhir' },
                                { name: 'berkas_surat_sehat', label: 'Surat keterangan sehat' },
                                { name: 'berkas_rekomendasi', label: 'Surat rekomendasi sekolah' },
                            ]" :key="field.name" :class="fieldClass">
                                <Label>{{ field.label }}</Label>
                                <Input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onFile(field.name as any, $event)" />
                                <p v-if="form.errors[field.name]" class="text-destructive text-xs">{{ form.errors[field.name] }}</p>
                            </div>
                            <div :class="fieldClass">
                                <Label>Piagam / sertifikat{{ selectedSyarat?.wajib_piagam ? ' (wajib)' : ' (opsional)' }}</Label>
                                <Input type="file" accept=".jpg,.jpeg,.png,.pdf,.webp" @change="onFile('berkas_piagam', $event)" />
                                <p v-if="form.errors.berkas_piagam" class="text-destructive text-xs">{{ form.errors.berkas_piagam }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="submit" :disabled="form.processing || !pendaftaranBuka">Kirim pendaftaran</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
