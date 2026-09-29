import type { PublicEventSummary } from '@/types/event';

export type EventPhase = 'upcoming' | 'ongoing' | 'finished' | 'scheduled';

export const EVENT_PHASE_LABEL: Record<EventPhase, string> = {
    upcoming: 'Akan datang',
    ongoing: 'Berlangsung',
    finished: 'Selesai',
    scheduled: 'Terjadwal',
};

const parseLocalDate = (value: string | null): Date | null => {
    if (!value) {
        return null;
    }

    const [y, m, d] = value.slice(0, 10).split('-').map(Number);

    return y && m && d ? new Date(y, m - 1, d) : null;
};

const todayStart = () => {
    const now = new Date();

    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
};

export const eventPhase = (event: PublicEventSummary): EventPhase => {
    if (event.status === 'selesai' || event.status === 'dibatalkan') {
        return 'finished';
    }

    const start = parseLocalDate(event.tanggal_mulai);
    const end = parseLocalDate(event.tanggal_selesai) ?? start;

    if (!start || !end) {
        return 'scheduled';
    }

    const today = todayStart();

    if (end < today) {
        return 'finished';
    }

    return start > today ? 'upcoming' : 'ongoing';
};

export const eventPhaseClass = (phase: EventPhase) =>
    phase === 'finished' ? 'bg-muted text-muted-foreground' : 'bg-(--wp-accent) text-(--wp-accent-contrast)';

export const formatEventDate = (value: string | null, month: 'short' | 'long' = 'short') => {
    const date = parseLocalDate(value);

    return date ? date.toLocaleDateString('id-ID', { day: 'numeric', month, year: 'numeric' }) : null;
};

export const eventDateRange = (event: PublicEventSummary, month: 'short' | 'long' = 'short') => {
    const start = formatEventDate(event.tanggal_mulai, month);
    const end = formatEventDate(event.tanggal_selesai, month);

    if (!start) {
        return 'Tanggal menyusul';
    }

    return !end || start === end ? start : `${start} – ${end}`;
};

/** Day number + short month for the calendar tile on cards. */
export const eventDateTile = (event: PublicEventSummary) => {
    const date = parseLocalDate(event.tanggal_mulai);

    if (!date) {
        return null;
    }

    return {
        day: date.getDate(),
        month: date.toLocaleDateString('id-ID', { month: 'short' }).replace('.', ''),
    };
};
