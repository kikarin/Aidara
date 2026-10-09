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

type PaymentOpenMode = 'after_approval' | 'before_event';

type PaymentOpenRule = {
    mode: PaymentOpenMode;
    days_before: number;
    message_waiting: string;
    message_open: string;
};

type JenisSewa = 'event' | 'reguler';

const props = defineProps<{
    settings: Record<string, SettingBag>;
    paymentOpenRules: Record<JenisSewa, PaymentOpenRule>;
    paymentOpenDefaults: Record<JenisSewa, PaymentOpenRule>;
    paymentPlaceholders: Record<string, string>;
}>();

const kategoriList: Array<{ key: JenisSewa; label: string; hint: string }> = [
    { key: 'event', label: 'Event', hint: 'Sewa per hari' },
    { key: 'reguler', label: 'Latihan', hint: 'Sewa per jam' },
];

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
    payment_open_rules: {
        event: { ...props.paymentOpenRules.event },
        reguler: { ...props.paymentOpenRules.reguler },
    } as Record<JenisSewa, PaymentOpenRule>,
});

const ruleError = (jenis: JenisSewa, field: keyof PaymentOpenRule) =>
    (form.errors as Record<string, string | undefined>)[`payment_open_rules.${jenis}.${field}`];

const resetMessages = (jenis: JenisSewa) => {
    form.payment_open_rules[jenis].message_waiting = props.paymentOpenDefaults[jenis].message_waiting;
    form.payment_open_rules[jenis].message_open = props.paymentOpenDefaults[jenis].message_open;
};

const formatTanggal = (d: Date) =>
    new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(
        d,
    );

/** Contoh kegiatan 14 hari dari sekarang jam 08:00, untuk pratinjau pesan. */
const previewMessage = (jenis: JenisSewa, template: string) => {
    const rule = form.payment_open_rules[jenis];
    const days = Math.max(1, Number(rule.days_before) || 1);
    const hours = Math.max(1, Number(form.payment_expire_hours) || 72);

    const kegiatan = new Date();
    kegiatan.setDate(kegiatan.getDate() + 14);
    kegiatan.setHours(8, 0, 0, 0);

    let buka = new Date();
    if (rule.mode === 'before_event') {
        buka = new Date(kegiatan);
        buka.setDate(buka.getDate() - days);
        buka.setHours(0, 0, 0, 0);
    }
    let batas = new Date(buka.getTime() + hours * 3_600_000);
    if (rule.mode === 'before_event' && batas > kegiatan) {
        batas = kegiatan;
    }

    const values: Record<string, string> = {
        '{nomor}': 'BK-2026-0001',
        '{jenis}': jenis === 'reguler' ? 'Latihan' : 'Event',
        '{h}': String(days),
        '{tanggal_kegiatan}': formatTanggal(kegiatan),
        '{tanggal_buka}': formatTanggal(buka),
        '{batas_bayar}': formatTanggal(batas),
        '{jam_bayar}': String(hours),
        '{kontak}': form.kontak_klarifikasi || '-',
    };

    return Object.entries(values).reduce((text, [key, value]) => text.split(key).join(value), template || '');
};

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
                        <label for="set-expire" class="sb-label">Batas waktu bayar setelah pembayaran dibuka (jam)</label>
                        <input
                            id="set-expire"
                            v-model.number="form.payment_expire_hours"
                            type="number"
                            min="1"
                            class="sb-input tabular-nums"
                            aria-describedby="set-expire-hint"
                        />
                        <p id="set-expire-hint" class="sb-hint">
                            Default 72 jam (3 hari). Lewat dari itu, booking otomatis batal. Untuk mode H- kegiatan, tenggat tidak melewati jam mulai
                            kegiatan.
                        </p>
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

                    <div class="space-y-4 sm:col-span-2">
                        <div>
                            <span class="sb-label">Waktu pembayaran dibuka per kategori</span>
                            <p class="sb-hint">
                                Event = sewa per hari, Latihan = sewa per jam. Atur apakah pembayaran langsung dibuka setelah disetujui, atau baru
                                dibuka H- sekian hari sebelum kegiatan.
                            </p>
                        </div>

                        <div
                            v-for="kat in kategoriList"
                            :key="kat.key"
                            class="space-y-4 rounded-2xl p-4 ring-1 ring-(--wp-hairline)"
                            :aria-labelledby="`rule-${kat.key}-title`"
                            role="group"
                        >
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h3 :id="`rule-${kat.key}-title`" class="text-sm font-semibold">
                                    {{ kat.label }} <span class="text-muted-foreground font-normal">({{ kat.hint }})</span>
                                </h3>
                                <button type="button" class="text-xs font-semibold text-(--wp-accent-strong)" @click="resetMessages(kat.key)">
                                    Pakai pesan default
                                </button>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <label
                                    v-for="opt in [
                                        {
                                            value: 'after_approval',
                                            title: 'Dibuka setelah disetujui',
                                            desc: 'Petunjuk bayar langsung muncul begitu booking disetujui.',
                                        },
                                        {
                                            value: 'before_event',
                                            title: 'Dibuka H- kegiatan',
                                            desc: 'Petunjuk bayar baru muncul beberapa hari sebelum kegiatan.',
                                        },
                                    ]"
                                    :key="opt.value"
                                    class="flex cursor-pointer gap-3 rounded-xl p-3 text-sm ring-1 transition"
                                    :class="
                                        form.payment_open_rules[kat.key].mode === opt.value
                                            ? 'bg-(--wp-accent-soft) ring-(--wp-accent)'
                                            : 'ring-(--wp-hairline) hover:ring-(--wp-accent)/50'
                                    "
                                >
                                    <input
                                        v-model="form.payment_open_rules[kat.key].mode"
                                        type="radio"
                                        :name="`rule-${kat.key}-mode`"
                                        :value="opt.value"
                                        class="mt-0.5 accent-(--wp-accent)"
                                    />
                                    <span>
                                        <span class="block font-medium">{{ opt.title }}</span>
                                        <span class="text-muted-foreground block text-xs">{{ opt.desc }}</span>
                                    </span>
                                </label>
                            </div>
                            <p v-if="ruleError(kat.key, 'mode')" class="sb-error">{{ ruleError(kat.key, 'mode') }}</p>

                            <div v-if="form.payment_open_rules[kat.key].mode === 'before_event'" class="max-w-xs">
                                <label :for="`rule-${kat.key}-days`" class="sb-label">Dibuka H- berapa hari sebelum kegiatan</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-muted-foreground text-sm font-semibold">H-</span>
                                    <input
                                        :id="`rule-${kat.key}-days`"
                                        v-model.number="form.payment_open_rules[kat.key].days_before"
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="sb-input tabular-nums"
                                        :aria-invalid="ruleError(kat.key, 'days_before') ? 'true' : undefined"
                                    />
                                </div>
                                <p v-if="ruleError(kat.key, 'days_before')" class="sb-error">{{ ruleError(kat.key, 'days_before') }}</p>
                            </div>

                            <div v-if="form.payment_open_rules[kat.key].mode === 'before_event'">
                                <label :for="`rule-${kat.key}-waiting`" class="sb-label">Pesan saat pembayaran belum dibuka</label>
                                <textarea
                                    :id="`rule-${kat.key}-waiting`"
                                    v-model="form.payment_open_rules[kat.key].message_waiting"
                                    rows="3"
                                    class="sb-input"
                                    :aria-invalid="ruleError(kat.key, 'message_waiting') ? 'true' : undefined"
                                />
                                <p v-if="ruleError(kat.key, 'message_waiting')" class="sb-error">{{ ruleError(kat.key, 'message_waiting') }}</p>
                                <p class="bg-muted/60 mt-2 rounded-lg px-3 py-2 text-xs leading-relaxed">
                                    <span class="text-muted-foreground">Pratinjau:</span>
                                    {{ previewMessage(kat.key, form.payment_open_rules[kat.key].message_waiting) }}
                                </p>
                            </div>

                            <div>
                                <label :for="`rule-${kat.key}-open`" class="sb-label">Pesan saat pembayaran sudah dibuka</label>
                                <textarea
                                    :id="`rule-${kat.key}-open`"
                                    v-model="form.payment_open_rules[kat.key].message_open"
                                    rows="3"
                                    class="sb-input"
                                    :aria-invalid="ruleError(kat.key, 'message_open') ? 'true' : undefined"
                                />
                                <p v-if="ruleError(kat.key, 'message_open')" class="sb-error">{{ ruleError(kat.key, 'message_open') }}</p>
                                <p class="bg-muted/60 mt-2 rounded-lg px-3 py-2 text-xs leading-relaxed">
                                    <span class="text-muted-foreground">Pratinjau:</span>
                                    {{ previewMessage(kat.key, form.payment_open_rules[kat.key].message_open) }}
                                </p>
                            </div>
                        </div>

                        <div class="text-muted-foreground text-xs leading-relaxed">
                            <span class="font-medium">Variabel pesan:</span>
                            <span v-for="(label, key) in paymentPlaceholders" :key="key" class="mr-3 inline-block">
                                <code class="text-foreground">{{ key }}</code> {{ label }}
                            </span>
                        </div>
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
