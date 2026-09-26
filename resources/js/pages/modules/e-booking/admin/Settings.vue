<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

type SettingBag = {
    value: unknown;
    description: string | null;
};

const props = defineProps<{
    settings: Record<string, SettingBag>;
}>();

const rek = computed(() => {
    const v = props.settings.rekening_transfer?.value;
    return v && typeof v === 'object' ? (v as Record<string, string>) : {};
});

const kop = computed(() => {
    const v = props.settings.surat_kop?.value;
    return v && typeof v === 'object' ? (v as Record<string, string>) : {};
});

const asString = (key: string) => {
    const v = props.settings[key]?.value;
    if (v == null) return '';
    if (typeof v === 'string' || typeof v === 'number') return String(v);

    return '';
};

const form = useForm({
    branding_name: asString('branding_name') || 'E-Booking',
    kontak_klarifikasi: asString('kontak_klarifikasi'),
    payment_mode: asString('payment_mode') || 'manual',
    payment_expire_hours: Number(asString('payment_expire_hours') || 72),
    pengajuan_sla_hari_kerja: Number(asString('pengajuan_sla_hari_kerja') || 7),
    rekening_bank: rek.value.bank || '',
    rekening_nomor: rek.value.rekening || '',
    rekening_atas_nama: rek.value.atas_nama || '',
    surat_instansi: kop.value.instansi || '',
    surat_alamat: kop.value.alamat || '',
    surat_email: kop.value.email || '',
    surat_telp: kop.value.telp || '',
    surat_penandatangan_nama: kop.value.penandatangan_nama || '',
    surat_penandatangan_jabatan: kop.value.penandatangan_jabatan || '',
});

const submit = () => {
    form.put(route('e-booking.admin.settings.update'), { preserveScroll: true });
};
</script>

<template>
    <SeoHead title="Pengaturan E-Booking" />

    <AdminLayout active="settings">
        <h1 class="text-foreground text-2xl font-bold">Pengaturan</h1>
        <p class="text-muted-foreground mt-1 text-sm">Atur pembayaran, nama layanan, dan kontak yang bisa dihubungi.</p>

        <p class="text-muted-foreground mt-3 text-sm">
            Blok tanggal/jam venue:
            <Link :href="route('e-booking.admin.closures.index')" class="font-semibold text-[var(--brand-green,#2e7d32)] hover:underline">
                Blok jadwal →
            </Link>
        </p>

        <form class="mt-6 max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-sm font-medium">Nama layanan</label>
                <input v-model="form.branding_name" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
                <InputError :message="form.errors.branding_name" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Kontak klarifikasi</label>
                <input v-model="form.kontak_klarifikasi" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
                <InputError :message="form.errors.kontak_klarifikasi" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Cara pembayaran</label>
                <SimpleSelect
                    v-model="form.payment_mode"
                    :options="[
                        { value: 'manual', label: 'Transfer manual' },
                        { value: 'bjb', label: 'BJB (belum aktif)' },
                    ]"
                    placeholder="Pilih cara bayar"
                    trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Batas waktu bayar setelah approval (jam)</label>
                <input
                    v-model.number="form.payment_expire_hours"
                    type="number"
                    min="1"
                    class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                />
                <p class="text-muted-foreground mt-1 text-xs">Default 72 jam (3 hari). Lewat dari itu, booking otomatis batal.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Lama proses pengajuan (hari kerja)</label>
                <input
                    v-model.number="form.pengajuan_sla_hari_kerja"
                    type="number"
                    min="1"
                    max="90"
                    class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                />
                <p class="text-muted-foreground mt-1 text-xs">Ditampilkan ke penyewa saat mengirim pengajuan. Default 7 hari kerja.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Bank</label>
                <input v-model="form.rekening_bank" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">No. rekening</label>
                <input v-model="form.rekening_nomor" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Atas nama</label>
                <input v-model="form.rekening_atas_nama" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
            </div>

            <div class="border-border space-y-4 rounded-xl border p-4">
                <p class="font-semibold">Kop surat balasan</p>
                <p class="text-muted-foreground -mt-2 text-xs">Dipakai pada PDF surat yang dikirim ke penyewa.</p>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Nama instansi</label>
                    <input v-model="form.surat_instansi" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Alamat</label>
                    <input v-model="form.surat_alamat" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Email</label>
                        <input
                            v-model="form.surat_email"
                            type="text"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Telepon</label>
                        <input v-model="form.surat_telp" type="text" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama penandatangan</label>
                        <input
                            v-model="form.surat_penandatangan_nama"
                            type="text"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Jabatan penandatangan</label>
                        <input
                            v-model="form.surat_penandatangan_jabatan"
                            type="text"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-green,#2e7d32)] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                :disabled="form.processing"
            >
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                Simpan
            </button>
        </form>
    </AdminLayout>
</template>
