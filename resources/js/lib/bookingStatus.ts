export type BookingStatusTone = 'success' | 'warning' | 'info' | 'danger' | 'neutral';

export type BookingStatusAudience = 'renter' | 'admin';

const RENTER_LABELS: Record<string, string> = {
    draft: 'Draf',
    menunggu_approval: 'Menunggu ditinjau',
    awaiting_payment: 'Menunggu pembayaran',
    awaiting_verification: 'Bukti sedang dicek',
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

const ADMIN_LABELS: Record<string, string> = {
    draft: 'Draf',
    menunggu_approval: 'Perlu ditinjau',
    awaiting_payment: 'Menunggu bayar',
    awaiting_verification: 'Bukti perlu dicek',
    approved: 'Disetujui',
    paid: 'Sudah bayar',
    confirmed: 'Dikonfirmasi',
    perlu_klarifikasi: 'Perlu klarifikasi',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
    forfeited: 'Hangus',
    expired: 'Kedaluwarsa',
    completed: 'Selesai',
};

const TONES: Record<string, BookingStatusTone> = {
    menunggu_approval: 'warning',
    perlu_klarifikasi: 'warning',
    awaiting_payment: 'info',
    awaiting_verification: 'info',
    approved: 'success',
    paid: 'success',
    confirmed: 'success',
    rejected: 'danger',
    forfeited: 'danger',
    cancelled: 'neutral',
    expired: 'neutral',
    completed: 'neutral',
    draft: 'neutral',
};

export const bookingStatusLabel = (status: string, audience: BookingStatusAudience = 'renter') =>
    (audience === 'admin' ? ADMIN_LABELS : RENTER_LABELS)[status] ?? status;

export const bookingStatusTone = (status: string): BookingStatusTone => TONES[status] ?? 'neutral';

/** Class list for a `.sb-badge` in the given tone. */
export const toneClass = (tone: BookingStatusTone) => `sb-badge sb-tone-${tone}`;

export const formatRupiah = (value: number | null | undefined) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value ?? 0);
