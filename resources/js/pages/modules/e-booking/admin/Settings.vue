<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRight, faCircleNotch, faFloppyDisk } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

library.add(faArrowRight, faCircleNotch, faFloppyDisk);

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
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Pengaturan</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">Atur nama layanan, pembayaran, rekening tujuan, dan kop surat balasan.</p>
            </div>
            <Link
                :href="route('e-booking.admin.closures.index')"
                class="wp-link-arrow inline-flex items-center gap-1.5 text-sm font-semibold text-(--wp-accent-strong)"
            >
                Blok tanggal dan jam venue
                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
            </Link>
        </div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <section class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10" aria-labelledby="settings-layanan">
                <div>
                    <h2 id="settings-layanan" class="text-base font-semibold tracking-tight">Layanan</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Nama yang tampil ke penyewa dan kontak untuk klarifikasi.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="set-branding" class="sb-label">Nama layanan</label>
                        <input
                            id="set-branding"
                            v-model="form.branding_name"
                            type="text"
                            class="sb-input"
                            :aria-invalid="form.errors.branding_name ? 'true' : undefined"
                        />
                        <p v-if="form.errors.branding_name" class="sb-error">{{ form.errors.branding_name }}</p>
                    </div>
                    <div>
                        <label for="set-kontak" class="sb-label">Kontak klarifikasi</label>
                        <input
                            id="set-kontak"
                            v-model="form.kontak_klarifikasi"
                            type="text"
                            class="sb-input"
                            :aria-invalid="form.errors.kontak_klarifikasi ? 'true' : undefined"
                        />
                        <p v-if="form.errors.kontak_klarifikasi" class="sb-error">{{ form.errors.kontak_klarifikasi }}</p>
                    </div>
                </div>
            </section>

            <section class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10" aria-labelledby="settings-pembayaran">
                <div>
                    <h2 id="settings-pembayaran" class="text-base font-semibold tracking-tight">Pembayaran dan proses</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Cara bayar, batas waktu pembayaran, dan lama proses pengajuan.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <span class="sb-label">Cara pembayaran</span>
                        <SimpleSelect
                            v-model="form.payment_mode"
                            :options="[
                                { value: 'manual', label: 'Transfer manual' },
                                { value: 'bjb', label: 'BJB (belum aktif)' },
                            ]"
                            placeholder="Pilih cara bayar"
                            trigger-class="h-11 w-full rounded-xl border-0 bg-background px-3.5 text-sm shadow-none ring-1 ring-(--wp-hairline) ring-inset"
                        />
                    </div>
                    <div>
                        <label for="set-expire" class="sb-label">Batas waktu bayar setelah disetujui (jam)</label>
                        <input
                            id="set-expire"
                            v-model.number="form.payment_expire_hours"
                            type="number"
                            min="1"
                            class="sb-input tabular-nums"
                            aria-describedby="set-expire-hint"
                        />
                        <p id="set-expire-hint" class="sb-hint">Default 72 jam (3 hari). Lewat dari itu, booking otomatis batal.</p>
                    </div>
                    <div>
                        <label for="set-sla" class="sb-label">Lama proses pengajuan (hari kerja)</label>
                        <input
                            id="set-sla"
                            v-model.number="form.pengajuan_sla_hari_kerja"
                            type="number"
                            min="1"
                            max="90"
                            class="sb-input tabular-nums"
                            aria-describedby="set-sla-hint"
                        />
                        <p id="set-sla-hint" class="sb-hint">Ditampilkan ke penyewa saat mengirim pengajuan. Default 7 hari kerja.</p>
                    </div>
                </div>
            </section>

            <section class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10" aria-labelledby="settings-rekening">
                <div>
                    <h2 id="settings-rekening" class="text-base font-semibold tracking-tight">Rekening transfer</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Rekening tujuan yang ditampilkan untuk pembayaran manual.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="set-bank" class="sb-label">Bank</label>
                        <input id="set-bank" v-model="form.rekening_bank" type="text" class="sb-input" />
                    </div>
                    <div>
                        <label for="set-rekening" class="sb-label">No. rekening</label>
                        <input id="set-rekening" v-model="form.rekening_nomor" type="text" inputmode="numeric" class="sb-input tabular-nums" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="set-atas-nama" class="sb-label">Atas nama</label>
                        <input id="set-atas-nama" v-model="form.rekening_atas_nama" type="text" class="sb-input" />
                    </div>
                </div>
            </section>

            <section class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10" aria-labelledby="settings-kop">
                <div>
                    <h2 id="settings-kop" class="text-base font-semibold tracking-tight">Kop surat balasan</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Dipakai pada PDF surat yang dikirim ke penyewa.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="set-instansi" class="sb-label">Nama instansi</label>
                        <input id="set-instansi" v-model="form.surat_instansi" type="text" class="sb-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="set-alamat" class="sb-label">Alamat</label>
                        <input id="set-alamat" v-model="form.surat_alamat" type="text" class="sb-input" />
                    </div>
                    <div>
                        <label for="set-email" class="sb-label">Email</label>
                        <input id="set-email" v-model="form.surat_email" type="text" class="sb-input" />
                    </div>
                    <div>
                        <label for="set-telp" class="sb-label">Telepon</label>
                        <input id="set-telp" v-model="form.surat_telp" type="text" class="sb-input tabular-nums" />
                    </div>
                    <div>
                        <label for="set-ttd-nama" class="sb-label">Nama penandatangan</label>
                        <input id="set-ttd-nama" v-model="form.surat_penandatangan_nama" type="text" class="sb-input" />
                    </div>
                    <div>
                        <label for="set-ttd-jabatan" class="sb-label">Jabatan penandatangan</label>
                        <input id="set-ttd-jabatan" v-model="form.surat_penandatangan_jabatan" type="text" class="sb-input" />
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="form.processing">
                    <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-3.5 animate-spin" aria-hidden="true" />
                    <FontAwesomeIcon v-else :icon="['fas', 'floppy-disk']" class="size-3.5" aria-hidden="true" />
                    Simpan pengaturan
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
