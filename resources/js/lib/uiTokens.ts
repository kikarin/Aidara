/** Placeholder avatar HTML untuk kolom foto di DataTable */
export const AVATAR_PLACEHOLDER_HTML =
    '<div class="flex h-12 w-12 items-center justify-center rounded-full bg-muted text-xs text-muted-foreground">No</div>';

export const IMAGE_PLACEHOLDER_HTML =
    '<div class="flex h-16 w-16 items-center justify-center rounded bg-muted text-xs text-muted-foreground">-</div>';

export const STAT_CHIP_CLASS = {
    atlet: 'stat-chip stat-chip-atlet',
    pelatih: 'stat-chip stat-chip-pelatih',
    tenaga: 'stat-chip stat-chip-tenaga',
} as const;

export const STAT_BADGE_CLASS = {
    atlet: 'stat-chip stat-chip-atlet hover:opacity-90',
    pelatih: 'stat-chip stat-chip-pelatih hover:opacity-90',
    tenaga: 'stat-chip stat-chip-tenaga hover:opacity-90',
} as const;

export const MUTED_BADGE_CLASS = 'badge-muted';

export const ACTIVE_BADGE_HTML = '<span class="badge-success">Aktif</span>';
export const INACTIVE_BADGE_HTML = '<span class="badge-muted">Nonaktif</span>';
