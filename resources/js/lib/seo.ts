export const SEO_APP_NAME = import.meta.env.VITE_APP_NAME || 'AIDARA';

export const SEO_DEFAULT_KEYWORDS =
    'aidara, dispora, kabupaten bogor, olahraga, atlet, pelatih, keolahragaan, data olahraga';

export const SEO_HOME_DESCRIPTION =
    'AIDARA adalah sistem informasi keolahragaan Dispora Kabupaten Bogor untuk pengelolaan data cabang olahraga, atlet, pelatih, dan tenaga pendukung.';

export const SEO_EVENTS_DESCRIPTION =
    'Daftar lengkap event dan kegiatan olahraga yang diselenggarakan oleh Dispora Kabupaten Bogor melalui platform AIDARA.';

export const SEO_WORLDCUP_KEYWORDS =
    'piala dunia 2026, piala dunia, world cup 2026, jadwal piala dunia, skor piala dunia, klasemen piala dunia, fifa world cup, live score piala dunia';

export const SEO_WORLDCUP_HOME_SNIPPET =
    'Saksikan jadwal, skor live, klasemen grup, dan bracket knockout Piala Dunia 2026 (FIFA World Cup) di AIDARA — Dispora Kabupaten Bogor.';

export const SEO_DEFAULT_OG_IMAGE = '/web-app-manifest-512x512.png';

export const SEO_SITE_NAME = SEO_APP_NAME;

export const SEO_OG_LOCALE = 'id_ID';

export function formatPageTitle(title?: string | null): string {
    if (!title?.trim()) {
        return SEO_APP_NAME;
    }

    if (title.includes(SEO_APP_NAME)) {
        return title;
    }

    return `${title} — ${SEO_APP_NAME}`;
}

export function absoluteUrl(path: string, baseUrl?: string): string {
    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    const base = (baseUrl ?? (typeof window !== 'undefined' ? window.location.origin : '')).replace(/\/$/, '');
    const normalized = path.startsWith('/') ? path : `/${path}`;

    return base ? `${base}${normalized}` : normalized;
}

export function resolveShareImage(image?: string | null, baseUrl?: string): string {
    return absoluteUrl(image?.trim() || SEO_DEFAULT_OG_IMAGE, baseUrl);
}

export function truncateDescription(value: string, maxLength = 160): string {
    const normalized = value.replace(/\s+/g, ' ').trim();

    if (normalized.length <= maxLength) {
        return normalized;
    }

    return `${normalized.slice(0, maxLength - 1).trimEnd()}…`;
}
