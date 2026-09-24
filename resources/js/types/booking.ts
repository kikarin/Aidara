export type BookingVenueSummary = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    areas_count: number;
};

export type BookingArea = {
    id: number;
    code: string;
    name: string;
    is_tentative: boolean;
};

export type BookingTarif = {
    id: number;
    code: string | null;
    area_id: number | null;
    uraian: string;
    satuan: string;
    tarif_pemerintah: number | null;
    tarif_non_pemerintah: number | null;
    time_slot: string | null;
    audience_type: string | null;
    day_type: string | null;
    vehicle_class: string | null;
    event_level: string | null;
    category: string | null;
    meta: Record<string, unknown> | null;
};

export type BookingAddon = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    harga: number | null;
};

export type BookingQuote = {
    subtotal: number;
    addon_total: number;
    grand_total: number;
    lines: Array<{
        uraian: string;
        satuan: string;
        qty: number;
        line_total: number;
        duration_label?: string;
    }>;
    addons: Array<{
        name: string;
        qty: number;
        line_total: number;
    }>;
};

export type BookingAvailability = {
    status: string;
    color?: string;
    bookable: boolean;
    within_horizon?: boolean;
    horizon_days?: number | null;
    closed?: boolean;
    closures?: Array<{
        id: number;
        venue_id: number;
        area_id: number | null;
        starts_at: string;
        ends_at: string;
        reason: string | null;
    }>;
};
