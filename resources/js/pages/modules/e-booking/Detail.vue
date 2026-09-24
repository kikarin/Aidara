<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

type PaymentInfo = {
    id: number;
    gateway: string;
    amount: number;
    status: string;
    bank: string | null;
    rekening: string | null;
    atas_nama: string | null;
    bukti_url: string | null;
    expires_at: string | null;
    expire_hours: number | null;
    notes: string | null;
};

const props = defineProps<{
    booking: {
        id: number;
        nomor: string;
        status: string;
        priority_flag: string | null;
        kategori_tarif: string;
        tujuan: string | null;
        keterangan: string | null;
        starts_at: string | null;
        ends_at: string | null;
        grand_total: number;
        subtotal: number;
        addon_total: number;
        can_upload_bukti: boolean;
        venue: { id: number; code: string; name: string } | null;
        area: { id: number; code: string; name: string } | null;
        items: Array<{ uraian: string; satuan: string; qty: number; line_total: number }>;
        addons: Array<{ name: string; qty: number; line_total: number }>;
        payment: PaymentInfo | null;
        status_logs: Array<{
            from_status: string | null;
            to_status: string | null;
            note: string | null;
            created_at: string | null;
        }>;
    };
}>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const flashError = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

const formatRp = (n: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'Menunggu ditinjau',
        awaiting_payment: 'Menunggu pembayaran',
        approved: 'Sudah disetujui',
        paid: 'Pembayaran masuk',
        confirmed: 'Sudah dikonfirmasi',
        perlu_klarifikasi: 'Perlu konfirmasi',
        rejected: 'Tidak disetujui',
        cancelled: 'Dibatalkan',
        forfeited: 'Hangus',
        expired: 'Lewat batas waktu',
        completed: 'Selesai',
    };

    return map[status] ?? status;
};

const form = useForm({
    bukti: null as File | null,
    notes: '',
});

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    form.bukti = input.files?.[0] ?? null;
};

const submitBukti = () => {
    form.post(route('e-booking.bookings.bukti', props.booking.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('bukti', 'notes');
        },
    });
};

const countdown = ref('');
let timer: ReturnType<typeof setInterval> | null = null;

const updateCountdown = () => {
    const raw = props.booking.payment?.expires_at;
    if (!raw) {
        countdown.value = '';

        return;
    }

    const end = new Date(raw.replace(' ', 'T')).getTime();
    const diff = end - Date.now();
    if (Number.isNaN(end) || diff <= 0) {
        countdown.value = 'Tenggat habis';

        return;
    }

    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    const s = Math.floor((diff % 60_000) / 1000);
    countdown.value = `${h}j ${m}m ${s}d`;
};

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const title = computed(() => {
    if (props.booking.status === 'awaiting_payment') {
        return 'Menunggu pembayaran';
    }
    if (props.booking.status === 'menunggu_approval') {
        return 'Menunggu ditinjau';
    }

    return `Pesanan ${props.booking.nomor}`;
});

const nextStepText = computed(() => {
    if (props.booking.status === 'awaiting_payment') {
        return 'Silakan transfer sesuai petunjuk di bawah, lalu kirim bukti pembayaran.';
    }
    if (props.booking.status === 'menunggu_approval') {
        return 'Pengajuan Anda sudah masuk. Pengelola akan meninjau terlebih dahulu.';
    }
    if (props.booking.status === 'perlu_klarifikasi') {
        return 'Pengelola perlu konfirmasi tambahan. Mohon cek catatan terbaru.';
    }

    return 'Lihat rincian pesanan dan pantau perkembangannya di halaman ini.';
});
</script>

<template>
    <SeoHead :title="`Booking ${booking.nomor}`" />

    <EBookingLayout active="history">
        <div
            v-if="flashSuccess"
            class="mb-4 rounded-[24px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ flashSuccess }}
        </div>
        <div
            v-if="flashError"
            class="mb-4 rounded-[24px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
            {{ flashError }}
        </div>

        <section class="mb-6 rounded-[32px] bg-[linear-gradient(135deg,#0f3d87_0%,#1479d1_58%,#44b6ff_100%)] p-6 text-white shadow-[0_20px_60px_rgba(20,121,209,0.25)] sm:p-8">
            <Link :href="route('e-booking.bookings.index')" class="text-sm font-semibold text-white/85 hover:text-white">
                ← Kembali ke pesanan saya
            </Link>
            <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ title }}</h1>
                    <p class="mt-2 text-sm text-white/85">{{ nextStepText }}</p>
                </div>
                <span class="rounded-full bg-white/15 px-4 py-2 text-sm font-semibold">
                    {{ statusLabel(booking.status) }}
                </span>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <div class="space-y-4">
                <div class="space-y-3 rounded-[28px] border border-slate-200 bg-white p-5 text-sm shadow-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Tempat</span>
                        <span class="text-right font-medium text-slate-900">{{ booking.venue?.name }}</span>
                    </div>
                    <div v-if="booking.area" class="flex justify-between gap-3">
                        <span class="text-slate-500">Area</span>
                        <span class="text-right text-slate-900">{{ booking.area.name }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Jadwal</span>
                        <span class="text-right text-slate-900">{{ booking.starts_at }} — {{ booking.ends_at }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Keperluan</span>
                        <span class="text-right text-slate-900">{{ booking.tujuan }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Jenis pemohon</span>
                        <span class="text-right text-slate-900">{{ booking.kategori_tarif === 'pemerintah' ? 'Instansi pemerintah' : 'Umum / non pemerintah' }}</span>
                    </div>
                    <div class="space-y-1 border-t border-slate-200 pt-3">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Biaya dasar</span>
                            <span class="text-slate-900">{{ formatRp(booking.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Tambahan layanan</span>
                            <span class="text-slate-900">{{ formatRp(booking.addon_total) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-base font-bold text-slate-900">
                            <span>Total</span>
                            <span>{{ formatRp(booking.grand_total) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="booking.items.length" class="rounded-[28px] border border-slate-200 bg-white p-5 text-sm shadow-sm">
                    <h2 class="mb-3 font-semibold text-slate-900">Rincian biaya</h2>
                    <ul class="space-y-2">
                        <li v-for="(item, i) in booking.items" :key="i" class="flex justify-between gap-3">
                            <span class="text-slate-600">
                                {{ item.uraian }}
                                <span class="text-xs">({{ item.satuan }} × {{ item.qty }})</span>
                            </span>
                            <span class="text-slate-900">{{ formatRp(item.line_total) }}</span>
                        </li>
                    </ul>
                </div>

                <div v-if="booking.status_logs.length" class="rounded-[28px] border border-slate-200 bg-white p-5 text-sm shadow-sm">
                    <h2 class="mb-3 font-semibold text-slate-900">Perjalanan pesanan</h2>
                    <ul class="space-y-2">
                        <li v-for="(log, i) in booking.status_logs" :key="i" class="text-slate-600">
                            <span class="font-medium text-slate-900">{{ statusLabel(log.to_status || '-') }}</span>
                            <template v-if="log.from_status"> dari {{ statusLabel(log.from_status) }}</template>
                            <span v-if="log.created_at"> · {{ log.created_at }}</span>
                            <span v-if="log.note" class="block text-xs">{{ log.note }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">Pembayaran</h2>

                    <template v-if="booking.payment">
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Status</span>
                                <span class="font-medium text-slate-900">{{ statusLabel(booking.payment.status) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-slate-500">Jumlah</span>
                                <span class="font-semibold text-slate-900">{{ formatRp(booking.payment.amount) }}</span>
                            </div>
                            <div v-if="booking.payment.bank" class="mt-3 space-y-1 border-t border-slate-200 pt-3">
                                <p class="font-medium text-slate-900">{{ booking.payment.bank }}</p>
                                <p class="font-mono text-sm tracking-wide">{{ booking.payment.rekening }}</p>
                                <p class="text-xs text-slate-500">Atas nama {{ booking.payment.atas_nama }}</p>
                            </div>
                            <p v-if="countdown" class="mt-2 text-xs font-medium text-amber-700">
                                Batas waktu bayar: {{ countdown }}
                                <template v-if="booking.payment.expires_at">
                                    ({{ booking.payment.expires_at }})
                                </template>
                            </p>
                            <div v-if="booking.payment.bukti_url" class="mt-3">
                                <p class="mb-1 text-xs text-slate-500">Bukti yang sudah dikirim</p>
                                <a
                                    :href="booking.payment.bukti_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-sm font-semibold text-[var(--brand-green,#2e7d32)] hover:underline"
                                >
                                    Lihat bukti →
                                </a>
                            </div>
                        </div>
                    </template>
                    <p v-else class="mt-3 text-sm text-slate-500">
                        Petunjuk pembayaran akan muncul setelah pengajuan Anda disetujui pengelola.
                    </p>

                    <form
                        v-if="booking.can_upload_bukti"
                        class="mt-5 space-y-3 border-t border-slate-200 pt-4"
                        @submit.prevent="submitBukti"
                    >
                        <p class="text-sm font-medium text-slate-900">Kirim bukti pembayaran</p>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.pdf"
                            required
                            class="block w-full text-sm text-slate-500"
                            @change="onFileChange"
                        />
                        <InputError :message="form.errors.bukti" />
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Catatan tambahan (boleh dikosongkan)"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                        />
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-green,#2e7d32)] px-4 py-3 text-sm font-semibold text-white disabled:opacity-60"
                            :disabled="form.processing || !form.bukti"
                        >
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Kirim bukti
                        </button>
                    </form>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('e-booking.bookings.index')"
                        class="rounded-full border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-800"
                    >
                        ← Pesanan saya
                    </Link>
                    <Link
                        :href="route('e-booking.catalog')"
                        class="rounded-full border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-800"
                    >
                        Cari tempat lain
                    </Link>
                </div>
            </aside>
        </div>
    </EBookingLayout>
</template>
