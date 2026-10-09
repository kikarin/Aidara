export type EventStatus = 'draft' | 'publish' | 'selesai' | 'dibatalkan';

export interface PublicEventSummary {
    id: number;
    nama_event: string;
    deskripsi: string | null;
    deskripsi_singkat?: string | null;
    foto_url: string | null;
    kategori_event_nama: string;
    tingkat_event_nama: string;
    lokasi: string | null;
    tanggal_mulai: string | null;
    tanggal_selesai: string | null;
    status: EventStatus;
    status_label: string;
}
