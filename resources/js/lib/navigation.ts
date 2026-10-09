const PESERTA_CONTEXT =
    /^(\/(?:atlet|pelatih|tenaga-pendukung)\/\d+)(?:\/(?:prestasi|dokumen|sertifikat|edit)(?:\/.*)?)?$/;

/**
 * Key untuk transisi halaman Inertia.
 * Perubahan query (?tab=, ?statistik=) dan navigasi tab dalam konteks peserta tidak memicu animasi.
 */
export function getPageTransitionKey(url: string): string {
    const pathname = url.split('?')[0].split('#')[0];

    const pesertaMatch = pathname.match(PESERTA_CONTEXT);
    if (pesertaMatch) {
        return pesertaMatch[1];
    }

    return pathname;
}
