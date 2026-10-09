const NAMA_HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

type ParsedParts = {
    year: number;
    month: number;
    day: number;
    hour: number | null;
    minute: number | null;
};

const parseParts = (value?: string | null): ParsedParts | null => {
    if (!value) return null;

    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/);
    if (!match) return null;

    return {
        year: Number(match[1]),
        month: Number(match[2]),
        day: Number(match[3]),
        hour: match[4] != null ? Number(match[4]) : null,
        minute: match[5] != null ? Number(match[5]) : null,
    };
};

const dayOfWeek = (parts: ParsedParts): number => new Date(Date.UTC(parts.year, parts.month - 1, parts.day)).getUTCDay();

export const formatTanggalIndo = (value?: string | null, withHari = true): string => {
    const parts = parseParts(value);
    if (!parts) return '';

    const tanggal = `${parts.day} ${NAMA_BULAN[parts.month - 1]} ${parts.year}`;

    return withHari ? `${NAMA_HARI[dayOfWeek(parts)]}, ${tanggal}` : tanggal;
};

export const formatTanggalSingkatIndo = (value?: string | null): string => {
    const parts = parseParts(value);
    if (!parts) return '';

    return `${parts.day} ${NAMA_BULAN[parts.month - 1].slice(0, 3)} ${parts.year}`;
};

export const formatJamIndo = (value?: string | null): string => {
    const parts = parseParts(value);
    if (!parts || parts.hour == null) return '';

    return `${String(parts.hour).padStart(2, '0')}:${String(parts.minute ?? 0).padStart(2, '0')}`;
};

export const formatTanggalJamIndo = (value?: string | null, withHari = true): string => {
    const tanggal = formatTanggalIndo(value, withHari);
    if (!tanggal) return '';

    const jam = formatJamIndo(value);

    return jam ? `${tanggal}, ${jam}` : tanggal;
};
