<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import BookingAvailabilityCalendar from '@/components/e-booking/BookingAvailabilityCalendar.vue';
import FacilityIcon from '@/components/e-booking/FacilityIcon.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { formatJamIndo, formatTanggalIndo, formatTanggalJamIndo } from '@/lib/format-tanggal';
import type { BookingAddon, BookingAvailability, BookingQuote, BookingTarif } from '@/types/booking';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { CalendarDays, Check, CircleAlert, LoaderCircle, Plus, ShieldCheck, Wallet, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type VenueDetail = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    areas: Array<{ id: number; code: string; name: string; is_tentative: boolean }>;
    facilities: Array<{ id: number; name: string; icon: string | null }>;
};

type DaySlot = {
    starts_at: string;
    ends_at: string;
    label: string;
    status: string;
    bookable: boolean;
    pengajuan?: boolean;
    reason: string | null;
};

type AreaRow = {
    uid: number;
    area_id: number | '';
    tarif_id: number | '';
    qty: number;
    luas_m2: number | '';
};

const props = defineProps<{
    venue: VenueDetail;
    tarifs: BookingTarif[];
    addons: BookingAddon[];
    terms: {
        terms: unknown;
        reminders?: Record<string, unknown>;
        policies?: Record<string, unknown> | null;
    };
    quote: BookingQuote | null;
    availability: BookingAvailability | null;
    oldForm?: {
        tarif_id?: number | string | null;
        area_id?: number | string | null;
        areas?: Array<{
            area_id?: number | string | null;
            tarif_id?: number | string | null;
            qty?: number | string | null;
            luas_m2?: number | string | null;
        }>;
        kategori_tarif?: string | null;
        starts_at?: string | null;
        ends_at?: string | null;
        qty?: number | string | null;
        luas_m2?: number | string | null;
        tujuan?: string | null;
        keterangan?: string | null;
        addon_ids?: Array<{ id: number; qty?: number } | number>;
    };
    branding: string;
    slaHariKerja?: number;
}>();

const page = usePage();
const flashError = computed(() => (page.props.flash as { error?: string } | undefined)?.error);
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);

const fromOldAddonIds = () => {
    const raw = props.oldForm?.addon_ids ?? [];
    return raw.map((item) => (typeof item === 'number' ? item : item?.id)).filter((id): id is number => typeof id === 'number');
};

const toDateInput = (value?: string | null) => {
    if (!value) {
        const d = new Date();
        d.setDate(d.getDate() + 1);
        return d.toISOString().slice(0, 10);
    }

    return value.replace(' ', 'T').slice(0, 10);
};

const initAreaRows = (): AreaRow[] => {
    const fromAreas = (props.oldForm?.areas ?? [])
        .filter((row) => row && row.area_id != null && row.area_id !== '')
        .map((row) => ({
            uid: ++areaRowSeq,
            area_id: Number(row.area_id),
            tarif_id: row.tarif_id != null && row.tarif_id !== '' ? Number(row.tarif_id) : ('' as number | ''),
            qty: Number(row.qty ?? 1) || 1,
            luas_m2: (row.luas_m2 === '' || row.luas_m2 == null ? '' : Number(row.luas_m2)) as number | '',
        }));

    if (fromAreas.length) {
        return fromAreas;
    }

    if (props.oldForm?.area_id != null && props.oldForm.area_id !== '') {
        return [
            {
                uid: ++areaRowSeq,
                area_id: Number(props.oldForm.area_id),
                tarif_id: props.oldForm.tarif_id != null && props.oldForm.tarif_id !== '' ? Number(props.oldForm.tarif_id) : ('' as number | ''),
                qty: Number(props.oldForm?.qty ?? 1) || 1,
                luas_m2: (props.oldForm?.luas_m2 === '' || props.oldForm?.luas_m2 == null ? '' : Number(props.oldForm.luas_m2)) as number | '',
            },
        ];
    }

    return [];
};

let areaRowSeq = 0;
const bookingMode = ref<'all' | 'areas'>('all');
const allAreasShortcut = ref(false);
const areaRows = ref<AreaRow[]>([]);

const selectedAddonIds = ref<number[]>(fromOldAddonIds());
const toggleAddon = (id: number) => {
    const index = selectedAddonIds.value.indexOf(id);
    if (index > -1) selectedAddonIds.value.splice(index, 1);
    else selectedAddonIds.value.push(id);
};
const selectedDate = ref(toDateInput(props.oldForm?.starts_at));
const endDate = ref(toDateInput(props.oldForm?.ends_at || props.oldForm?.starts_at));
const durationHours = ref(1);
const durationDays = ref(1);
const durationMonths = ref(1);
const durationBlocks = ref(1);
const daySlots = ref<DaySlot[]>([]);
const dayFullDay = ref(false);
const dayFullDayReason = ref<string | null>(null);
const dayPartialNotes = ref<string[]>([]);
const slotsLoading = ref(false);
const slotsError = ref('');
const selectedSlotKey = ref('');
const quoteLoading = ref(false);
const localQuote = ref<BookingQuote | null>(props.quote);
const localAvailability = ref<BookingAvailability | null>(props.availability);
const quoteError = ref('');
const rangeReady = ref(false);

const needsLuas = (satuan: string) => satuan === 'per_m2_day' || satuan === 'per_m2_month';
const needsQty = (satuan: string) => ['per_person', 'per_court_hour', 'per_unit_3hour', 'per_match'].includes(satuan);

const form = useForm({
    kategori_tarif: (props.oldForm?.kategori_tarif as 'pemerintah' | 'non_pemerintah') || 'non_pemerintah',
    starts_at: props.oldForm?.starts_at ? props.oldForm.starts_at.replace(' ', 'T').slice(0, 16) : '',
    ends_at: props.oldForm?.ends_at ? props.oldForm.ends_at.replace(' ', 'T').slice(0, 16) : '',
    qty: Number(props.oldForm?.qty ?? 1) || 1,
    luas_m2: (props.oldForm?.luas_m2 as number | '') ?? ('' as number | ''),
    tujuan: props.oldForm?.tujuan ?? '',
    keterangan: props.oldForm?.keterangan ?? '',
    terms_accepted: false,
    tata_tertib_accepted: false,
    addon_ids: [] as Array<{ id: number; qty: number }>,
});

areaRows.value = initAreaRows();
if (areaRows.value.length) {
    bookingMode.value = 'areas';
}

const allTarifId = ref<number | ''>(props.oldForm?.tarif_id != null && props.oldForm.tarif_id !== '' ? Number(props.oldForm.tarif_id) : '');

const venueWideTarifs = computed(() => props.tarifs.filter((t) => t.area_id === null));
const hasVenueWideTarif = computed(() => venueWideTarifs.value.length > 0);

const selectedAreaIds = computed(() => areaRows.value.filter((row) => row.area_id !== '').map((row) => Number(row.area_id)));

const tarifsForArea = (areaId: number | '') => {
    if (areaId === '') {
        return props.tarifs;
    }

    return props.tarifs.filter((t) => t.area_id === null || t.area_id === Number(areaId));
};

const tarifForRow = (row: AreaRow) => props.tarifs.find((t) => t.id === Number(row.tarif_id)) ?? null;

const selectedTarif = computed(() => props.tarifs.find((t) => t.id === Number(allTarifId.value)) ?? null);

const firstRowTarif = computed(() => (areaRows.value.length ? tarifForRow(areaRows.value[0]) : null));
const activeTarif = computed(() => (bookingMode.value === 'all' ? selectedTarif.value : firstRowTarif.value));

const selectedAreaName = computed(() => {
    if (bookingMode.value === 'all' || allAreasShortcut.value) {
        return 'Semua area';
    }

    const names = selectedAreaIds.value
        .map((id) => props.venue.areas.find((area) => area.id === id)?.name)
        .filter((name): name is string => Boolean(name));

    return names.length ? names.join(', ') : 'Area terpilih';
});

const selectedTarifNames = computed(() => {
    if (allAreasShortcut.value) {
        return `Seluruh venue · ${areaRows.value.length} area, harga per area`;
    }

    if (bookingMode.value === 'all') {
        return selectedTarif.value?.uraian ?? '-';
    }

    const names = areaRows.value.map((row) => tarifForRow(row)?.uraian).filter((name): name is string => Boolean(name));

    return names.length ? names.join(', ') : '-';
});

/** hourly | block3 | daily | monthly | event */
const scheduleMode = computed(() => {
    const satuan = activeTarif.value?.satuan ?? 'per_hour';
    if (satuan === 'per_unit_3hour') {
        return 'block3';
    }
    if (['per_day', 'per_activity_day', 'per_m2_day'].includes(satuan)) {
        return 'daily';
    }
    if (satuan === 'per_m2_month') {
        return 'monthly';
    }
    if (['per_match', 'per_person'].includes(satuan)) {
        return 'event';
    }

    return 'hourly';
});

const usesHourSlots = computed(() => scheduleMode.value === 'hourly' || scheduleMode.value === 'block3');

const operatingHours = computed(() => {
    const hours = props.terms.policies?.operating_hours as { start?: string; end?: string } | undefined;

    return {
        start: hours?.start || '06:00',
        end: hours?.end || '21:00',
    };
});

const durationOptions = computed(() => {
    const meta = activeTarif.value?.meta ?? {};
    const min = typeof meta.min_hours === 'number' ? Math.max(1, Number(meta.min_hours)) : 1;
    const max = typeof meta.max_hours === 'number' ? Math.max(min, Number(meta.max_hours)) : Math.max(min, 4);
    const options: number[] = [];
    for (let h = min; h <= Math.min(max, 8); h++) {
        options.push(h);
    }

    return options.length ? options : [1, 2, 3];
});

const blockOptions = computed(() => [1, 2, 3, 4]);
const dayOptions = computed(() => [1, 2, 3, 4, 5, 7, 14, 30]);
const monthOptions = computed(() => [1, 2, 3, 6, 12]);

const setBookingMode = (mode: 'all' | 'areas') => {
    if (mode === 'all' && !hasVenueWideTarif.value) {
        selectAllAreas();
        allAreasShortcut.value = true;
        bookingMode.value = 'areas';
        refreshSchedule();

        return;
    }

    allAreasShortcut.value = false;
    bookingMode.value = mode;
    if (mode === 'areas' && areaRows.value.length === 0) {
        addAreaRow();
    }
    refreshSchedule();
};

const selectAllAreas = () => {
    const rows: AreaRow[] = [];
    for (const area of props.venue.areas) {
        const tarif = tarifsForArea(area.id)[0];
        if (!tarif) {
            continue;
        }
        rows.push({
            uid: ++areaRowSeq,
            area_id: area.id,
            tarif_id: tarif.id,
            qty: 1,
            luas_m2: '',
        });
    }
    areaRows.value = rows;
};

const addAreaRow = () => {
    allAreasShortcut.value = false;
    const used = new Set(selectedAreaIds.value);
    const free = props.venue.areas.filter((area) => !used.has(area.id));
    if (free.length === 0) {
        return;
    }

    const first = tarifsForArea(free[0].id)[0];
    areaRows.value.push({
        uid: ++areaRowSeq,
        area_id: free[0].id,
        tarif_id: first?.id ?? ('' as number | ''),
        qty: 1,
        luas_m2: '',
    });
};

const removeAreaRow = (uid: number) => {
    allAreasShortcut.value = false;
    areaRows.value = areaRows.value.filter((row) => row.uid !== uid);
};

const onRowAreaChange = (row: AreaRow) => {
    allAreasShortcut.value = false;
    const options = tarifsForArea(row.area_id);
    const stillValid = options.some((t) => t.id === Number(row.tarif_id));
    if (!stillValid) {
        row.tarif_id = options[0]?.id ?? ('' as number | '');
    }
    clearQuote();
};

const areaRowOptions = (row: AreaRow) =>
    props.venue.areas
        .filter((area) => area.id === Number(row.area_id) || !selectedAreaIds.value.includes(area.id))
        .map((area) => ({
            value: area.id,
            label: area.is_tentative ? `${area.name} (jadwal bisa berubah)` : area.name,
        }));

const tarifRowOptions = (row: AreaRow) =>
    tarifsForArea(row.area_id).map((tarif) => ({
        value: tarif.id,
        label: tarif.uraian,
    }));

const canAddAreaRow = computed(() => {
    if (bookingMode.value !== 'areas') {
        return false;
    }

    return selectedAreaIds.value.length < props.venue.areas.length;
});

const tarifSelectOptions = computed(() =>
    venueWideTarifs.value.map((tarif) => ({
        value: tarif.id,
        label: tarif.uraian,
    })),
);

const kategoriSelectOptions = computed(() => [
    {
        value: 'non_pemerintah',
        label: 'Umum / non pemerintah',
        disabled: activeTarif.value?.tarif_non_pemerintah == null,
    },
    {
        value: 'pemerintah',
        label: 'Instansi pemerintah',
        disabled: activeTarif.value?.tarif_pemerintah == null,
    },
]);

const durationHourOptions = computed(() => durationOptions.value.map((h) => ({ value: h, label: `${h} jam` })));
const durationBlockSelectOptions = computed(() => blockOptions.value.map((n) => ({ value: n, label: `${n} blok (${n * 3} jam)` })));
const durationDaySelectOptions = computed(() => dayOptions.value.map((n) => ({ value: n, label: `${n} hari` })));
const durationMonthSelectOptions = computed(() => monthOptions.value.map((n) => ({ value: n, label: `${n} bulan` })));

const scheduleTitle = computed(() => {
    switch (scheduleMode.value) {
        case 'daily':
            return 'Pilih tanggal pemakaian';
        case 'monthly':
            return 'Pilih periode bulanan';
        case 'event':
            return 'Pilih tanggal kegiatan';
        case 'block3':
            return 'Pilih blok jam';
        default:
            return 'Pilih tanggal dan jam';
    }
});

const scheduleHint = computed(() => {
    switch (scheduleMode.value) {
        case 'daily':
            return 'Tanggal hijau masih bisa dipakai. Harga mengikuti jumlah hari.';
        case 'monthly':
            return 'Pilih bulan dan tanggal mulai sewa.';
        case 'event':
            return 'Pilih tanggal pelaksanaan kegiatan.';
        case 'block3':
            return 'Satu blok berdurasi 3 jam. Klik blok yang masih hijau.';
        default:
            return 'Tanggal hijau masih terbuka — klik untuk melihat jam yang tersedia.';
    }
});

const formatRp = (n: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const jenisPemohonLabel = (value: string) => (value === 'pemerintah' ? 'Instansi pemerintah' : 'Umum / non pemerintah');

const satuanLabel = (satuan: string) => {
    const map: Record<string, string> = {
        per_hour: 'per jam',
        per_court_hour: 'per lapangan per jam',
        per_day: 'per hari',
        per_activity_day: 'per hari kegiatan',
        per_m2_day: 'per meter per hari',
        per_m2_month: 'per meter per bulan',
        per_unit_3hour: 'per blok 3 jam',
        per_match: 'per pertandingan',
        per_person: 'per orang',
    };

    return map[satuan] ?? satuan;
};

const qtyLabel = computed(() => {
    const satuan = activeTarif.value?.satuan;
    if (satuan === 'per_person') {
        return 'Jumlah orang';
    }
    if (satuan === 'per_match') {
        return 'Jumlah pertandingan';
    }
    if (satuan === 'per_court_hour') {
        return 'Jumlah lapangan';
    }
    if (satuan === 'per_unit_3hour') {
        return 'Jumlah unit';
    }

    return 'Jumlah';
});

const toApiDatetime = (local: string) => {
    if (!local) {
        return local;
    }
    if (local.includes(' ')) {
        return local.length === 16 ? `${local}:00` : local;
    }

    return local.replace('T', ' ') + (local.length === 16 ? ':00' : '');
};

const addDays = (dateStr: string, days: number) => {
    const d = new Date(`${dateStr}T12:00:00`);
    d.setDate(d.getDate() + days);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
};

const addMonths = (dateStr: string, months: number) => {
    const d = new Date(`${dateStr}T12:00:00`);
    d.setMonth(d.getMonth() + months);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
};

const buildRangeFromDates = (startDate: string, finishDate: string) => {
    const start = `${startDate} ${operatingHours.value.start}:00`;
    // ends_at exclusive-ish for pricing days: use end of last day at closing, or next day 00:00
    // Pricing uses startOfDay diff + 1, so same calendar day start/end works if ends_at is later same day
    const end = `${finishDate} ${operatingHours.value.end}:00`;

    return { starts_at: start, ends_at: end };
};

const slotKey = (slot: DaySlot) => `${slot.starts_at}|${slot.ends_at}`;

const clearQuote = () => {
    localQuote.value = null;
    localAvailability.value = null;
    quoteError.value = '';
    selectedSlotKey.value = '';
    rangeReady.value = false;
    form.starts_at = '';
    form.ends_at = '';
};

const fetchQuoteForRange = async (startsAt: string, endsAt: string) => {
    quoteLoading.value = true;
    quoteError.value = '';
    localQuote.value = null;
    localAvailability.value = null;

    try {
        const validRows = areaRows.value.filter((row) => row.area_id !== '' && row.tarif_id !== '');
        if (bookingMode.value === 'areas' && validRows.length === 0) {
            throw new Error('Pilih area dan jenis sewa terlebih dahulu.');
        }

        const base = {
            kategori_tarif: form.kategori_tarif,
            starts_at: startsAt,
            ends_at: endsAt,
            addon_ids: selectedAddonIds.value.map((id) => ({ id, qty: 1 })),
        };

        const body =
            bookingMode.value === 'all'
                ? {
                      ...base,
                      tarif_id: Number(allTarifId.value),
                      qty: form.qty,
                      luas_m2: form.luas_m2 === '' ? null : form.luas_m2,
                  }
                : {
                      ...base,
                      areas: validRows.map((row) => ({
                          area_id: Number(row.area_id),
                          tarif_id: Number(row.tarif_id),
                          qty: row.qty,
                          luas_m2: row.luas_m2 === '' ? null : row.luas_m2,
                      })),
                  };

        const availParams = new URLSearchParams({
            venue_id: String(props.venue.id),
            starts_at: startsAt,
            ends_at: endsAt,
        });
        for (const areaId of selectedAreaIds.value) {
            availParams.append('area_ids[]', String(areaId));
        }

        const [quoteRes, availRes] = await Promise.all([
            fetch('/api/booking/public/quote', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            }),
            fetch(`/api/booking/public/availability?${availParams.toString()}`, {
                headers: { Accept: 'application/json' },
            }),
        ]);

        const quoteJson = await quoteRes.json();
        const availJson = await availRes.json();

        if (!quoteRes.ok || !quoteJson.success) {
            throw new Error(quoteJson.message || 'Gagal menghitung harga.');
        }
        if (!availRes.ok || !availJson.success) {
            throw new Error(availJson.message || 'Gagal cek ketersediaan.');
        }

        localQuote.value = quoteJson.data;
        localAvailability.value = availJson.data;
        form.starts_at = startsAt.replace(' ', 'T').slice(0, 16);
        form.ends_at = endsAt.replace(' ', 'T').slice(0, 16);
        rangeReady.value = true;
        selectedSlotKey.value = `${startsAt}|${endsAt}`;
    } catch (e) {
        quoteError.value = e instanceof Error ? e.message : 'Gagal menghitung harga.';
        rangeReady.value = false;
    } finally {
        quoteLoading.value = false;
    }
};

const applyDateBasedSchedule = async () => {
    if (!selectedDate.value || usesHourSlots.value) {
        return;
    }

    // Tarif m² butuh luas dulu
    if (selectedTarif.value && needsLuas(selectedTarif.value.satuan) && (form.luas_m2 === '' || form.luas_m2 == null)) {
        quoteError.value = 'Isi luas area (m²) dulu untuk menghitung harga.';
        rangeReady.value = false;

        return;
    }

    let finish = selectedDate.value;

    if (scheduleMode.value === 'daily') {
        finish = addDays(selectedDate.value, Math.max(1, durationDays.value) - 1);
        endDate.value = finish;
    } else if (scheduleMode.value === 'monthly') {
        // inclusive: 1 month ≈ same day next month minus 1 day; pricing uses ceil(days/30)
        finish = addDays(addMonths(selectedDate.value, Math.max(1, durationMonths.value)), -1);
        endDate.value = finish;
    } else {
        // event: single day
        finish = selectedDate.value;
        endDate.value = finish;
    }

    const range = buildRangeFromDates(selectedDate.value, finish);
    await fetchQuoteForRange(range.starts_at, range.ends_at);
};

const loadDaySlots = async () => {
    if (!selectedDate.value || !usesHourSlots.value) {
        return;
    }

    slotsLoading.value = true;
    slotsError.value = '';
    clearQuote();

    const duration = scheduleMode.value === 'block3' ? Math.max(1, durationBlocks.value) * 3 : Math.max(1, durationHours.value);
    const step = scheduleMode.value === 'block3' ? 3 : 1;

    try {
        const params = new URLSearchParams({
            date: selectedDate.value,
            duration_hours: String(duration),
            step_hours: String(step),
        });
        for (const areaId of selectedAreaIds.value) {
            params.append('area_ids[]', String(areaId));
        }

        const res = await fetch(`${route('e-booking.venues.day-slots', props.venue.id)}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Gagal memuat jadwal.');
        }
        daySlots.value = json.data.slots ?? [];
        dayFullDay.value = Boolean(json.data.full_day);
        dayFullDayReason.value = json.data.full_day_reason ?? null;
        dayPartialNotes.value = json.data.partial_notes ?? [];
    } catch (e) {
        daySlots.value = [];
        dayFullDay.value = false;
        dayFullDayReason.value = null;
        dayPartialNotes.value = [];
        slotsError.value = e instanceof Error ? e.message : 'Gagal memuat jadwal.';
    } finally {
        slotsLoading.value = false;
    }
};

const selectSlot = async (slot: DaySlot) => {
    if (!slot.bookable) {
        return;
    }

    selectedSlotKey.value = slotKey(slot);
    await fetchQuoteForRange(slot.starts_at, slot.ends_at);
};

const refreshSchedule = async () => {
    clearQuote();
    if (usesHourSlots.value) {
        await loadDaySlots();
    } else {
        daySlots.value = [];
        dayFullDay.value = false;
        dayFullDayReason.value = null;
        dayPartialNotes.value = [];
        slotsError.value = '';
        await applyDateBasedSchedule();
    }
};

const areaKey = computed(() => `${bookingMode.value}:${selectedAreaIds.value.join(',')}`);

const requote = () => {
    if (usesHourSlots.value) {
        if (selectedSlotKey.value && form.starts_at && form.ends_at) {
            fetchQuoteForRange(toApiDatetime(form.starts_at), toApiDatetime(form.ends_at));
        }

        return;
    }

    if (selectedDate.value) {
        applyDateBasedSchedule();
    }
};

watch(areaKey, () => {
    const opts = durationOptions.value;
    if (!opts.includes(durationHours.value)) {
        durationHours.value = opts[0] ?? 1;
    }
    refreshSchedule();
});

watch(allTarifId, () => {
    const opts = durationOptions.value;
    if (!opts.includes(durationHours.value)) {
        durationHours.value = opts[0] ?? 1;
    }
    refreshSchedule();
});

const rowTarifQtyKey = computed(() => areaRows.value.map((row) => `${row.uid}.${row.tarif_id}.${row.qty}.${row.luas_m2}`).join('|'));

watch(rowTarifQtyKey, () => {
    const opts = durationOptions.value;
    if (!opts.includes(durationHours.value)) {
        durationHours.value = opts[0] ?? 1;
    }
    requote();
});

watch([selectedDate, durationHours, durationBlocks, durationDays, durationMonths], () => {
    refreshSchedule();
});

watch(selectedAddonIds, () => {
    if (usesHourSlots.value) {
        if (selectedSlotKey.value && form.starts_at && form.ends_at) {
            fetchQuoteForRange(toApiDatetime(form.starts_at), toApiDatetime(form.ends_at));
        }

        return;
    }

    if (selectedDate.value) {
        applyDateBasedSchedule();
    }
});

watch(
    () => form.kategori_tarif,
    () => {
        if (usesHourSlots.value) {
            if (selectedSlotKey.value && form.starts_at && form.ends_at) {
                fetchQuoteForRange(toApiDatetime(form.starts_at), toApiDatetime(form.ends_at));
            }

            return;
        }

        if (selectedDate.value) {
            applyDateBasedSchedule();
        }
    },
);

watch(
    () => form.qty,
    () => {
        if (usesHourSlots.value) {
            if (selectedSlotKey.value && form.starts_at && form.ends_at) {
                fetchQuoteForRange(toApiDatetime(form.starts_at), toApiDatetime(form.ends_at));
            }

            return;
        }

        if (selectedDate.value) {
            applyDateBasedSchedule();
        }
    },
);

watch(
    () => form.luas_m2,
    () => {
        if (form.luas_m2 === '' || form.luas_m2 == null) {
            return;
        }

        // Mode harian/bulanan m²: tanggal bisa dipilih sebelum luas — refetch saat luas diisi
        if (!usesHourSlots.value) {
            if (selectedDate.value) {
                applyDateBasedSchedule();
            }

            return;
        }

        if (selectedSlotKey.value && form.starts_at && form.ends_at) {
            fetchQuoteForRange(toApiDatetime(form.starts_at), toApiDatetime(form.ends_at));
        }
    },
);

onMounted(() => {
    if (bookingMode.value === 'all' && venueWideTarifs.value.length === 0) {
        bookingMode.value = 'areas';
    }
    if (bookingMode.value === 'areas' && areaRows.value.length === 0) {
        addAreaRow();
    }
    if (bookingMode.value === 'all' && allTarifId.value === '') {
        allTarifId.value = venueWideTarifs.value[0]?.id ?? '';
    }
    const opts = durationOptions.value;
    durationHours.value = opts[0] ?? 1;
    refreshSchedule();
});

const availabilityCopy = computed(() => {
    if (!localAvailability.value) {
        return null;
    }

    if (localAvailability.value.status === 'hijau' && localAvailability.value.pengajuan) {
        return {
            label: 'Ada pengajuan lain di waktu ini',
            detail: 'Anda tetap bisa mengirim pengajuan — pengelola akan meninjau semua pengajuan yang masuk.',
            tone: 'text-orange-700',
        };
    }

    const map: Record<string, { label: string; detail: string; tone: string }> = {
        hijau: {
            label: 'Masih tersedia',
            detail: 'Waktu ini bisa diajukan.',
            tone: 'text-emerald-700',
        },
        merah: {
            label: 'Belum bisa dipakai',
            detail: 'Pilih waktu lain yang masih tersedia.',
            tone: 'text-red-700',
        },
    };

    return map[localAvailability.value.status] ?? null;
});

const slaHariKerja = computed(() => props.slaHariKerja ?? 7);

const canSubmit = computed(
    () =>
        !!bookingAuth.value &&
        !!localQuote.value &&
        !!localAvailability.value?.bookable &&
        !!form.starts_at &&
        !!form.ends_at &&
        !!form.tujuan &&
        form.terms_accepted &&
        form.tata_tertib_accepted &&
        !form.processing &&
        !quoteLoading.value,
);

const submitHint = computed(() => {
    if (!bookingAuth.value) {
        return 'Masuk dulu untuk mengirim pengajuan.';
    }
    if (!selectedSlotKey.value && !rangeReady.value) {
        return usesHourSlots.value ? 'Pilih tanggal dan jam yang masih tersedia.' : 'Pilih tanggal pemakaian terlebih dahulu.';
    }
    if (quoteLoading.value) {
        return 'Sedang menghitung harga…';
    }
    if (!localQuote.value) {
        return quoteError.value || 'Belum ada ringkasan harga.';
    }
    if (!localAvailability.value?.bookable) {
        return 'Waktu yang dipilih sudah tidak tersedia. Pilih yang lain.';
    }
    if (!form.tujuan) {
        return 'Isi keperluan pemakaian terlebih dahulu.';
    }
    if (!form.terms_accepted) {
        return 'Centang persetujuan aturan di bawah.';
    }
    if (!form.tata_tertib_accepted) {
        return 'Centang pernyataan bahwa Anda sudah membaca tata tertib.';
    }

    return '';
});

const termsData = computed(() => {
    const t = props.terms?.terms;
    if (t && typeof t === 'object') {
        const obj = t as { title?: string; points?: string[] };

        return {
            title: obj.title ?? null,
            points: Array.isArray(obj.points) ? obj.points.filter((p) => typeof p === 'string' && p.trim() !== '') : [],
        };
    }

    return { title: null, points: [] as string[] };
});

const termsTitle = computed(() => termsData.value.title);
const termsPoints = computed(() => termsData.value.points);

const termsText = computed(() => {
    const t = props.terms?.terms;
    if (typeof t === 'string') {
        return t;
    }
    if (t && typeof t === 'object' && 'html' in (t as object)) {
        return String((t as { html?: string }).html ?? '');
    }

    return 'Saya sudah membaca dan menyetujui aturan pemakaian fasilitas ini.';
});

const submitBooking = () => {
    if (!canSubmit.value) {
        return;
    }

    form.transform(() => {
        const base = {
            kategori_tarif: form.kategori_tarif,
            starts_at: toApiDatetime(form.starts_at),
            ends_at: toApiDatetime(form.ends_at),
            tujuan: form.tujuan,
            keterangan: form.keterangan,
            addon_ids: selectedAddonIds.value.map((id) => ({ id, qty: 1 })),
            terms_accepted: 1,
            tata_tertib_accepted: 1,
        };

        if (bookingMode.value === 'all') {
            return {
                ...base,
                tarif_id: Number(allTarifId.value),
                qty: form.qty,
                luas_m2: form.luas_m2 === '' ? null : form.luas_m2,
            };
        }

        return {
            ...base,
            areas: areaRows.value
                .filter((row) => row.area_id !== '' && row.tarif_id !== '')
                .map((row) => ({
                    area_id: Number(row.area_id),
                    tarif_id: Number(row.tarif_id),
                    qty: row.qty,
                    luas_m2: row.luas_m2 === '' ? null : row.luas_m2,
                })),
        };
    }).post(route('e-booking.bookings.store'), {
        preserveScroll: true,
        onFinish: () => form.transform((data) => data),
    });
};

const slotClass = (slot: DaySlot) => {
    const selected = selectedSlotKey.value === slotKey(slot);
    if (selected) {
        return 'border-sky-600 bg-sky-600 text-white shadow-sm';
    }
    if (slot.status === 'hijau' && slot.bookable) {
        return 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:border-emerald-400';
    }

    return 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400';
};

const slotTitle = (slot: DaySlot) => {
    const parts: string[] = [];
    if (slot.reason) {
        parts.push(slot.reason);
    } else if (slot.bookable) {
        parts.push('Tersedia');
    }
    if (slot.bookable && slot.pengajuan) {
        parts.push('Ada pengajuan lain — masih bisa diajukan');
    }

    return parts.join(' • ') || slot.label;
};
</script>

<template>
    <SeoHead :title="venue.name" :description="venue.description || `Sewa ${venue.name}`" />

    <EBookingLayout active="catalog">
        <div v-if="flashError" class="mb-5 rounded-[24px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
            {{ flashError }}
        </div>

        <section class="overflow-hidden rounded-[32px] bg-white shadow-sm">
            <div class="aspect-[16/9] bg-slate-100 sm:aspect-[21/9]">
                <AppImage v-if="venue.cover_url" :src="venue.cover_url" :alt="venue.name" class="size-full object-cover" />
            </div>
            <div class="p-6 sm:p-7">
                <Link :href="route('e-booking.catalog')" class="text-sm font-semibold text-sky-700 hover:underline"> ← Lihat tempat lain </Link>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ venue.name }}</h1>

                <div class="mt-4 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    <div>
                        <p v-if="venue.description" class="text-sm leading-6 text-slate-600">{{ venue.description }}</p>

                        <div v-if="venue.facilities && venue.facilities.length" class="mt-4">
                            <p class="text-sm font-semibold text-slate-900">Fasilitas</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="facility in venue.facilities"
                                    :key="facility.id"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700"
                                >
                                    <FacilityIcon :icon="facility.icon" class="size-4 text-sky-700" />
                                    {{ facility.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="termsPoints.length" id="tata-tertib" class="scroll-mt-40 rounded-2xl border border-amber-200 bg-amber-50/70 p-4">
                        <div class="flex items-start gap-2">
                            <ShieldCheck class="mt-0.5 size-5 shrink-0 text-amber-700" />
                            <div>
                                <p class="font-semibold text-slate-900">{{ termsTitle || 'Tata tertib' }}</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-slate-700">
                                    <li v-for="(point, index) in termsPoints" :key="index">{{ point }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.35fr_0.85fr]">
            <div class="space-y-5">
                <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700">1</div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Pilih area & jenis sewa</h2>
                            <p class="mt-1 text-sm text-slate-600">Sewa seluruh venue, atau pilih beberapa area sekaligus.</p>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            :class="
                                bookingMode === 'all' || allAreasShortcut
                                    ? 'border-sky-600 bg-sky-600 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                            "
                            @click="setBookingMode('all')"
                        >
                            Seluruh venue
                        </button>
                        <button
                            type="button"
                            class="rounded-full border px-4 py-2 text-sm font-semibold transition"
                            :class="
                                bookingMode === 'areas' && !allAreasShortcut
                                    ? 'border-sky-600 bg-sky-600 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                            "
                            :disabled="venue.areas.length === 0"
                            @click="setBookingMode('areas')"
                        >
                            Area tertentu
                        </button>
                    </div>

                    <div v-if="allAreasShortcut" class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-800">
                        Seluruh area venue ini dipilih otomatis karena belum ada tarif khusus "semua area". Harga dihitung per area — silakan
                        sesuaikan jenis sewa tiap area bila perlu.
                    </div>

                    <template v-if="bookingMode === 'all'">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Jenis sewa (seluruh venue)</label>
                                <SimpleSelect v-model="allTarifId" :options="tarifSelectOptions" placeholder="Pilih jenis sewa" required />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Jenis pemohon</label>
                                <SimpleSelect v-model="form.kategori_tarif" :options="kategoriSelectOptions" placeholder="Pilih jenis pemohon" />
                                <p class="mt-1 text-xs text-slate-500">Harga menyesuaikan jenis pemohon.</p>
                            </div>
                            <div v-if="selectedTarif && needsQty(selectedTarif.satuan)">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">{{ qtyLabel }}</label>
                                <input
                                    v-model.number="form.qty"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                                />
                            </div>
                            <div v-if="selectedTarif && needsLuas(selectedTarif.satuan)">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Luas area (m²)</label>
                                <input
                                    v-model.number="form.luas_m2"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                                />
                            </div>
                        </div>

                        <div v-if="selectedTarif" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                            <p class="font-semibold text-slate-900">{{ selectedTarif.uraian }}</p>
                            <p class="mt-1">Perhitungan: {{ satuanLabel(selectedTarif.satuan) }}</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                                <span v-if="selectedTarif.tarif_pemerintah != null">
                                    Instansi pemerintah: <strong>{{ formatRp(selectedTarif.tarif_pemerintah) }}</strong>
                                </span>
                                <span v-if="selectedTarif.tarif_non_pemerintah != null">
                                    Umum / non-pemerintah: <strong>{{ formatRp(selectedTarif.tarif_non_pemerintah) }}</strong>
                                </span>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div class="space-y-3">
                            <div v-for="row in areaRows" :key="row.uid" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-800">Area</label>
                                        <SimpleSelect
                                            :model-value="row.area_id"
                                            :options="areaRowOptions(row)"
                                            placeholder="Pilih area"
                                            @update:model-value="
                                                (val: string | number) => {
                                                    row.area_id = val === '' ? '' : Number(val);
                                                    onRowAreaChange(row);
                                                }
                                            "
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-slate-800">Jenis sewa</label>
                                        <SimpleSelect
                                            :model-value="row.tarif_id"
                                            :options="tarifRowOptions(row)"
                                            placeholder="Pilih jenis sewa"
                                            @update:model-value="
                                                (val: string | number) => {
                                                    row.tarif_id = val === '' ? '' : Number(val);
                                                }
                                            "
                                        />
                                    </div>
                                    <button
                                        v-if="areaRows.length > 1"
                                        type="button"
                                        class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-3 text-sm text-slate-500 transition hover:border-red-200 hover:text-red-600"
                                        title="Hapus area ini"
                                        @click="removeAreaRow(row.uid)"
                                    >
                                        <X class="size-4" />
                                    </button>
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div v-if="tarifForRow(row) && needsQty(tarifForRow(row)!.satuan)">
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">{{ qtyLabel }}</label>
                                        <input
                                            v-model.number="row.qty"
                                            type="number"
                                            min="1"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                        />
                                    </div>
                                    <div v-if="tarifForRow(row) && needsLuas(tarifForRow(row)!.satuan)">
                                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Luas area (m²)</label>
                                        <input
                                            v-model.number="row.luas_m2"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm"
                                        />
                                    </div>
                                </div>
                            </div>

                            <button
                                v-if="canAddAreaRow"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-dashed border-sky-300 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:border-sky-400"
                                @click="addAreaRow"
                            >
                                <Plus class="size-4" />
                                Tambah area
                            </button>
                        </div>

                        <div class="mt-4">
                            <label class="mb-1.5 block text-sm font-medium text-slate-800">Jenis pemohon</label>
                            <SimpleSelect v-model="form.kategori_tarif" :options="kategoriSelectOptions" placeholder="Pilih jenis pemohon" />
                            <p class="mt-1 text-xs text-slate-500">Berlaku untuk semua area — total harga dihitung per area.</p>
                        </div>
                    </template>
                </section>

                <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700">2</div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">{{ scheduleTitle }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ scheduleHint }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                        <div>
                            <p class="mb-2 text-sm font-medium text-slate-800">Kalender ketersediaan</p>
                            <BookingAvailabilityCalendar v-model="selectedDate" :venue-id="venue.id" :area-ids="selectedAreaIds" />
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">
                                    {{ scheduleMode === 'monthly' ? 'Mulai dari tanggal' : 'Tanggal terpilih' }}
                                </label>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ selectedDate ? formatTanggalIndo(selectedDate) : 'Belum dipilih' }}
                                </div>
                            </div>

                            <div v-if="scheduleMode === 'hourly'">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Lama pakai</label>
                                <SimpleSelect v-model="durationHours" :options="durationHourOptions" placeholder="Pilih lama" />
                            </div>

                            <div v-else-if="scheduleMode === 'block3'">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Jumlah blok</label>
                                <SimpleSelect v-model="durationBlocks" :options="durationBlockSelectOptions" placeholder="Pilih blok" />
                            </div>

                            <div v-else-if="scheduleMode === 'daily'">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Lama hari</label>
                                <SimpleSelect v-model="durationDays" :options="durationDaySelectOptions" placeholder="Pilih lama hari" />
                            </div>

                            <div v-else-if="scheduleMode === 'monthly'">
                                <label class="mb-1.5 block text-sm font-medium text-slate-800">Lama bulan</label>
                                <SimpleSelect v-model="durationMonths" :options="durationMonthSelectOptions" placeholder="Pilih lama bulan" />
                            </div>

                            <div v-else class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Sewa dihitung per kegiatan / kunjungan pada tanggal terpilih.
                            </div>
                        </div>
                    </div>

                    <!-- Slot jam: hanya untuk tarif per jam / blok 3 jam -->
                    <div v-if="usesHourSlots" class="mt-5">
                        <div v-if="slotsLoading" class="flex items-center gap-2 text-sm text-slate-500">
                            <LoaderCircle class="size-4 animate-spin" />
                            Memuat jadwal…
                        </div>
                        <p v-else-if="slotsError" class="text-sm text-red-600">{{ slotsError }}</p>
                        <div v-else-if="dayFullDay" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-800">
                            <p class="font-semibold">Tanggal penuh</p>
                            <p class="mt-1">{{ dayFullDayReason || 'Ditutup pengelola' }} — tanggal ini tidak bisa dipesan.</p>
                        </div>
                        <template v-else>
                            <p
                                v-if="dayPartialNotes.length"
                                class="mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
                            >
                                Sebagian jam ditutup: {{ dayPartialNotes.join(', ') }}
                            </p>
                            <p v-if="daySlots.length === 0" class="text-sm text-slate-500">Belum ada jam yang bisa dipilih untuk tanggal ini.</p>
                            <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                                <button
                                    v-for="slot in daySlots"
                                    :key="slotKey(slot)"
                                    type="button"
                                    class="relative rounded-2xl border px-3 py-3 text-left text-sm font-semibold transition"
                                    :class="slotClass(slot)"
                                    :disabled="!slot.bookable"
                                    :title="slotTitle(slot)"
                                    @click="selectSlot(slot)"
                                >
                                    <span class="block">{{ slot.label }}</span>
                                    <span class="mt-1 block text-[11px] font-medium opacity-80">
                                        <template v-if="slot.bookable && slot.pengajuan">Ada pengajuan</template>
                                        <template v-else-if="slot.bookable">Tersedia</template>
                                        <template v-else>{{ slot.reason || 'Penuh' }}</template>
                                    </span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Ringkas periode untuk tarif harian / bulanan / kegiatan -->
                    <div v-else class="mt-5 rounded-2xl border border-sky-100 bg-sky-50 px-4 py-4 text-sm text-slate-700">
                        <template v-if="quoteLoading">
                            <span class="inline-flex items-center gap-2 text-slate-500">
                                <LoaderCircle class="size-4 animate-spin" />
                                Mengecek ketersediaan…
                            </span>
                        </template>
                        <template v-else-if="form.starts_at && form.ends_at">
                            <p class="font-semibold text-slate-900">Periode terpilih</p>
                            <p class="mt-1">
                                {{ selectedDate ? formatTanggalIndo(selectedDate) : '' }}
                                <template v-if="endDate && endDate !== selectedDate"> — {{ formatTanggalIndo(endDate) }}</template>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">Jam operasional {{ operatingHours.start }}–{{ operatingHours.end }}</p>
                        </template>
                        <template v-else> Pilih tanggal di atas. Harga dan ketersediaan dihitung otomatis. </template>
                    </div>

                    <InputError :message="form.errors.starts_at || form.errors.ends_at" />
                </section>

                <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                    <div class="mb-5 flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700">3</div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Lengkapi pengajuan</h2>
                            <p class="mt-1 text-sm text-slate-600">Isi keperluan dan tambahan layanan bila perlu.</p>
                            <p class="mt-2 rounded-xl border border-sky-100 bg-sky-50 px-3 py-2 text-xs text-sky-800">
                                Pengajuan diproses maksimal {{ slaHariKerja }} hari kerja. Setelah disetujui, selesaikan pembayaran sebelum batas
                                waktu — jika terlewat, booking otomatis batal dan harus diajukan ulang.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800">Dipakai untuk apa?</label>
                        <input
                            v-model="form.tujuan"
                            type="text"
                            required
                            maxlength="255"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm"
                            placeholder="Contoh: latihan klub, sparring, kegiatan komunitas"
                        />
                        <InputError :message="form.errors.tujuan" />
                    </div>

                    <div v-if="addons.length" class="mt-5 space-y-3">
                        <p class="text-sm font-medium text-slate-800">Tambahan layanan (opsional)</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="addon in addons"
                                :key="addon.id"
                                type="button"
                                class="flex items-start justify-between gap-3 rounded-2xl border p-4 text-left text-sm transition"
                                :class="
                                    selectedAddonIds.includes(addon.id)
                                        ? 'border-sky-500 bg-sky-50'
                                        : 'border-slate-200 bg-slate-50 hover:border-slate-300'
                                "
                                @click="toggleAddon(addon.id)"
                            >
                                <span>
                                    <span class="block font-medium" :class="selectedAddonIds.includes(addon.id) ? 'text-sky-800' : 'text-slate-900'">
                                        {{ addon.name }}
                                    </span>
                                    <span v-if="addon.harga != null" class="mt-1 block text-xs font-semibold text-slate-700">
                                        {{ formatRp(addon.harga) }}
                                    </span>
                                </span>
                                <Check v-if="selectedAddonIds.includes(addon.id)" class="mt-0.5 size-4 shrink-0 text-sky-600" />
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-5 lg:sticky lg:top-36 lg:self-start">
                <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-7">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Ringkasan</h2>
                            <p class="mt-1 text-sm text-slate-500">Cek dulu sebelum mengirim.</p>
                        </div>
                        <div class="rounded-2xl bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            {{ selectedAreaName }}
                        </div>
                    </div>

                    <div class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Tempat</span>
                            <span class="text-right font-medium text-slate-900">{{ venue.name }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Area</span>
                            <span class="text-right text-slate-900">{{ selectedAreaName }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Jenis sewa</span>
                            <span class="text-right text-slate-900">{{ selectedTarifNames }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Jenis pemohon</span>
                            <span class="text-right text-slate-900">{{ jenisPemohonLabel(form.kategori_tarif) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">Jadwal</span>
                            <span class="text-right text-slate-900">
                                <template v-if="form.starts_at && form.ends_at">
                                    <template v-if="usesHourSlots">
                                        {{ formatTanggalJamIndo(form.starts_at) }} — {{ formatJamIndo(form.ends_at) }}
                                    </template>
                                    <template v-else>
                                        {{ selectedDate ? formatTanggalIndo(selectedDate) : '' }}
                                        <template v-if="endDate && endDate !== selectedDate"> — {{ formatTanggalIndo(endDate) }}</template>
                                    </template>
                                </template>
                                <template v-else>Belum dipilih</template>
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="localAvailability && availabilityCopy"
                        class="mt-5 rounded-2xl border px-4 py-4 text-sm"
                        :class="{
                            'border-emerald-200 bg-emerald-50': localAvailability.status === 'hijau' && !localAvailability.pengajuan,
                            'border-orange-200 bg-orange-50': localAvailability.status === 'hijau' && localAvailability.pengajuan,
                            'border-red-200 bg-red-50': localAvailability.status === 'merah',
                        }"
                    >
                        <p class="font-semibold" :class="availabilityCopy.tone">{{ availabilityCopy.label }}</p>
                        <p class="mt-1 text-slate-600">{{ availabilityCopy.detail }}</p>
                    </div>

                    <div v-if="quoteLoading" class="mt-5 flex items-center gap-2 text-sm text-slate-500">
                        <LoaderCircle class="size-4 animate-spin" />
                        Menghitung harga…
                    </div>
                    <p v-else-if="quoteError" class="mt-5 text-sm text-red-600">{{ quoteError }}</p>
                    <div v-else-if="localQuote" class="mt-5 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm">
                        <div v-for="(line, index) in localQuote.lines" :key="index" class="flex justify-between gap-3">
                            <span class="text-slate-600">
                                {{ line.uraian }}<template v-if="line.area"> · {{ line.area.name }}</template>
                                <span v-if="line.duration_label" class="block text-xs text-slate-500">{{ line.duration_label }}</span>
                            </span>
                            <span class="font-medium text-slate-900">{{ formatRp(line.line_total) }}</span>
                        </div>
                        <div v-if="localQuote.addons?.length" class="border-t border-slate-200 pt-3">
                            <div v-for="(addon, index) in localQuote.addons" :key="index" class="mt-2 flex justify-between gap-3">
                                <span class="text-slate-600">{{ addon.name }}</span>
                                <span class="font-medium text-slate-900">{{ formatRp(addon.line_total) }}</span>
                            </div>
                        </div>
                        <div class="border-t border-slate-200 pt-3">
                            <div class="flex justify-between gap-3 text-base font-bold text-slate-900">
                                <span>Perkiraan total</span>
                                <span>{{ formatRp(localQuote.grand_total) }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-5 text-sm text-slate-500">
                        {{ usesHourSlots ? 'Pilih jam di sebelah kiri untuk melihat harga.' : 'Pilih tanggal di sebelah kiri untuk melihat harga.' }}
                    </p>

                    <label class="mt-5 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
                        <input v-model="form.tata_tertib_accepted" type="checkbox" class="mt-1" />
                        <span class="text-slate-600">
                            Saya sudah membaca
                            <a
                                v-if="termsPoints.length"
                                href="#tata-tertib"
                                class="font-semibold text-sky-700 underline decoration-sky-300 underline-offset-2 hover:text-sky-900"
                            >
                                tata tertib
                            </a>
                            <template v-else>tata tertib</template>
                            di atas dan akan mematuhinya.
                        </span>
                    </label>
                    <InputError :message="form.errors.tata_tertib_accepted" />

                    <label class="mt-3 flex items-start gap-3 rounded-2xl bg-slate-50 p-4 text-sm">
                        <input v-model="form.terms_accepted" type="checkbox" class="mt-1" />
                        <span class="text-slate-600">{{ termsText }}</span>
                    </label>
                    <InputError :message="form.errors.terms_accepted" />

                    <button
                        v-if="bookingAuth"
                        type="button"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-green,#2e7d32)] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSubmit"
                        @click="submitBooking"
                    >
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Kirim pengajuan
                    </button>
                    <p v-if="bookingAuth && submitHint" class="mt-2 text-center text-xs text-amber-700">
                        {{ submitHint }}
                    </p>

                    <div v-else class="mt-5 space-y-3">
                        <Link
                            :href="route('e-booking.login')"
                            class="inline-flex w-full items-center justify-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
                        >
                            Masuk untuk lanjut
                        </Link>
                        <Link
                            :href="route('e-booking.register')"
                            class="inline-flex w-full items-center justify-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800"
                        >
                            Buat akun baru
                        </Link>
                        <p class="text-center text-xs text-slate-500">Anda tetap bisa cek jadwal dan harga tanpa masuk.</p>
                    </div>
                </section>

                <section class="rounded-[32px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <CircleAlert class="mt-0.5 size-5 text-amber-600" />
                        <div>
                            <h2 class="font-semibold text-slate-900">Setelah dikirim</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                Pengelola akan meninjau. Jika disetujui, petunjuk pembayaran muncul di halaman pesanan.
                            </p>
                        </div>
                    </div>
                </section>

                <div class="grid gap-3 text-sm text-slate-600">
                    <div class="flex items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-sm">
                        <CalendarDays class="size-4 text-sky-700" />
                        {{ usesHourSlots ? 'Pilih jam dari daftar yang tersedia' : 'Jadwal menyesuaikan satuan tarif (hari / bulan / kegiatan)' }}
                    </div>
                    <div class="flex items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-sm">
                        <Wallet class="size-4 text-emerald-700" />
                        Harga muncul otomatis setelah jadwal dipilih
                    </div>
                    <div class="flex items-center gap-2 rounded-2xl bg-white px-4 py-3 shadow-sm">
                        <ShieldCheck class="size-4 text-amber-700" />
                        Kirim pengajuan hanya jika jadwal masih terbuka
                    </div>
                </div>
            </aside>
        </div>
    </EBookingLayout>
</template>
