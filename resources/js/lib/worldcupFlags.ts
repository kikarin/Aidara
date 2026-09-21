const FIFA_TO_ISO: Record<string, string> = {
    ALB: 'AL',
    ALG: 'DZ',
    ARG: 'AR',
    ARM: 'AM',
    AUS: 'AU',
    AUT: 'AT',
    BEL: 'BE',
    BIH: 'BA',
    BRA: 'BR',
    CAN: 'CA',
    CHI: 'CL',
    COL: 'CO',
    CRO: 'HR',
    CZE: 'CZ',
    DEN: 'DK',
    ECU: 'EC',
    EGY: 'EG',
    ENG: 'GB',
    ESP: 'ES',
    FIN: 'FI',
    FRA: 'FR',
    GER: 'DE',
    GHA: 'GH',
    GRE: 'GR',
    HUN: 'HU',
    ISL: 'IS',
    IRN: 'IR',
    ITA: 'IT',
    JAM: 'JM',
    JPN: 'JP',
    KOR: 'KR',
    MAR: 'MA',
    MEX: 'MX',
    NED: 'NL',
    NGA: 'NG',
    NOR: 'NO',
    PAR: 'PY',
    PER: 'PE',
    POL: 'PL',
    POR: 'PT',
    QAT: 'QA',
    ROU: 'RO',
    KSA: 'SA',
    SCO: 'GB',
    SEN: 'SN',
    SRB: 'RS',
    SVK: 'SK',
    SVN: 'SI',
    RSA: 'ZA',
    SUI: 'CH',
    SWE: 'SE',
    TUN: 'TN',
    TUR: 'TR',
    UKR: 'UA',
    USA: 'US',
    URU: 'UY',
    WAL: 'GB',
};

function isoToEmoji(isoCode: string): string | null {
    if (!/^[A-Z]{2}$/.test(isoCode)) {
        return null;
    }

    return isoCode
        .split('')
        .map((char) => String.fromCodePoint(0x1f1e6 + char.charCodeAt(0) - 65))
        .join('');
}

export function fifaCodeToEmoji(code?: string | null): string | null {
    if (!code) {
        return null;
    }

    const normalized = code.trim().toUpperCase();

    if (normalized.length === 2) {
        return isoToEmoji(normalized);
    }

    if (normalized.length === 3) {
        const iso = FIFA_TO_ISO[normalized];

        if (iso) {
            return isoToEmoji(iso);
        }
    }

    return null;
}
