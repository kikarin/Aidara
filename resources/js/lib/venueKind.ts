import type { IconName } from '@fortawesome/fontawesome-svg-core';

export type VenueKind = 'stadion' | 'gedung' | 'tenis' | 'sirkuit' | 'akuatik' | 'serbaguna';

export const VENUE_KIND: Record<VenueKind, { label: string; icon: IconName }> = {
    stadion: { label: 'Stadion & atletik', icon: 'futbol' },
    gedung: { label: 'Gedung olahraga', icon: 'basketball' },
    tenis: { label: 'Lapangan tenis', icon: 'table-tennis-paddle-ball' },
    sirkuit: { label: 'Sirkuit', icon: 'flag-checkered' },
    akuatik: { label: 'Kolam renang', icon: 'person-swimming' },
    serbaguna: { label: 'Serbaguna', icon: 'layer-group' },
};

const RULES: Array<[VenueKind, RegExp]> = [
    ['akuatik', /aquatic|akuatik|kolam|renang/i],
    ['sirkuit', /sirkuit|road race|motocross|balap/i],
    ['tenis', /tenn?is/i],
    ['stadion', /^stadion|lintasan|atletik/i],
    ['gedung', /gedung|gelanggang|\bgor\b|laga/i],
];

/** Venues carry no category column, so the kind is inferred from the name (display only). */
export const venueKind = (name: string): VenueKind => RULES.find(([, pattern]) => pattern.test(name))?.[0] ?? 'serbaguna';
