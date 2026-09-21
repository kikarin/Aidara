export type Appearance = 'light' | 'slate' | 'warm' | 'sport' | 'dispora' | 'dark' | 'system';
export type ResolvedTheme = 'light' | 'slate' | 'warm' | 'sport' | 'dispora' | 'dark';

export const APPEARANCE_OPTIONS: Appearance[] = ['light', 'slate', 'warm', 'sport', 'dispora', 'dark', 'system'];
export const RESOLVED_THEMES: ResolvedTheme[] = ['light', 'slate', 'warm', 'sport', 'dispora', 'dark'];

export const DEFAULT_APPEARANCE: Appearance = 'dispora';

/** Migrasi pilihan lama (default / pure-white light) ke skema baru */
export function migrateAppearance(stored: string | null): Appearance {
    if (!stored) {
        return DEFAULT_APPEARANCE;
    }

    if (stored === 'default') {
        return 'light';
    }

    if (APPEARANCE_OPTIONS.includes(stored as Appearance)) {
        return stored as Appearance;
    }

    return DEFAULT_APPEARANCE;
}

export function resolveTheme(appearance: Appearance): ResolvedTheme {
    if (appearance === 'system') {
        if (typeof window === 'undefined') {
            return 'dispora';
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'dispora';
    }

    if (RESOLVED_THEMES.includes(appearance as ResolvedTheme)) {
        return appearance as ResolvedTheme;
    }

    return 'dispora';
}

export function applyResolvedTheme(resolved: ResolvedTheme): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.theme = resolved;
    document.documentElement.classList.toggle('dark', resolved === 'dark');
}

export function getStoredAppearance(): Appearance | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const stored = localStorage.getItem('appearance');

    if (!stored) {
        return null;
    }

    return migrateAppearance(stored);
}

export function getResolvedTheme(): ResolvedTheme {
    if (typeof document !== 'undefined' && document.documentElement.dataset.theme) {
        const theme = document.documentElement.dataset.theme;

        if (theme === 'default') {
            return 'light';
        }

        if (RESOLVED_THEMES.includes(theme as ResolvedTheme)) {
            return theme as ResolvedTheme;
        }
    }

    return resolveTheme(getStoredAppearance() || DEFAULT_APPEARANCE);
}

export function isDarkTheme(): boolean {
    return getResolvedTheme() === 'dark';
}

const CHART_FORE_COLORS: Record<ResolvedTheme, string> = {
    light: '#1e293b',
    slate: '#1e293b',
    warm: '#292524',
    sport: '#14532d',
    dispora: '#2e7d32',
    dark: '#f4f4f5',
};

const CHART_BORDER_COLORS: Record<ResolvedTheme, string> = {
    light: '#e2e8f0',
    slate: '#cbd5e1',
    warm: '#e7e5e4',
    sport: '#bbf7d0',
    dispora: '#c8e6c9',
    dark: '#374151',
};

export function getChartForeColor(): string {
    return CHART_FORE_COLORS[getResolvedTheme()];
}

export function getChartMutedColor(): string {
    return isDarkTheme() ? '#9ca3af' : '#64748b';
}

export function getChartBorderColor(): string {
    return CHART_BORDER_COLORS[getResolvedTheme()];
}
