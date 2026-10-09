<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import BookingAvailabilityCalendar from '@/components/e-booking/BookingAvailabilityCalendar.vue';
import FacilityIcon from '@/components/e-booking/FacilityIcon.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Skeleton } from '@/components/ui/skeleton';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { formatJamIndo, formatTanggalIndo, formatTanggalJamIndo } from '@/lib/format-tanggal';
import type { BookingAddon, BookingAvailability, BookingQuote, BookingTarif } from '@/types/booking';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faCalendarDays,
    faCheck,
    faCircleExclamation,
    faCircleInfo,
    faCircleNotch,
    faHashtag,
    faImage,
    faLayerGroup,
    faPaperPlane,
    faPlus,
    faShieldHalved,
    faWallet,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

library.add(
    faArrowLeft,
    faCalendarDays,
    faCheck,
    faCircleExclamation,
    faCircleInfo,
    faCircleNotch,
    faHashtag,
    faImage,
    faLayerGroup,
    faPaperPlane,
    faPlus,
    faShieldHalved,
    faWallet,
    faXmark,
);

const selectTriggerClass = 'h-11 w-full rounded-xl border-(--wp-hairline) bg-background px-3.5 text-sm shadow-none';

const previewArea = ref<VenueArea | null>(null);
const openAreaPreview = (area: VenueArea | undefined) => {
    if (area?.photo_url) previewArea.value = area;
};
const areaById = (id: number | '') => (id === '' ? undefined : props.venue.areas.find((area) => area.id === Number(id)));
const areasWithPhoto = computed(() => props.venue.areas.filter((area) => area.photo_url));

type VenueDetail = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    areas: VenueArea[];
    facilities: Array<{ id: number; name: string; icon: string | null }>;
};

type VenueArea = { id: number; code: string; name: string; photo_url: string | null; is_tentative: boolean };

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
const bookingMode = ref<'all' | 'areas'>('areas');
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
    surat_permohonan: null as File | null,
    addon_ids: [] as Array<{ id: number; qty: number }>,
});

areaRows.value = initAreaRows();
if (areaRows.value.length) {
    bookingMode.value = 'areas';
}

const allTarifId = ref<number | ''>(props.oldForm?.tarif_id != null && props.oldForm.tarif_id !== '' ? Number(props.oldForm.tarif_id) : '');

const venueWideTarifs = computed(() => props.tarifs.filter((t) => t.area_id === null));

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

/** Satuan yang berarti sewa per jam (langsung booking, tanpa surat permohonan). */
const SATUAN_PER_JAM = ['per_hour', 'per_court_hour', 'per_unit_3hour', 'per_match', 'per_person'];

const selectedSatuans = computed(() => {
    if (bookingMode.value === 'all') {
        return selectedTarif.value ? [selectedTarif.value.satuan] : [];
    }

    return areaRows.value
        .filter((row) => row.area_id !== '' && row.tarif_id !== '')
        .map((row) => tarifForRow(row)?.satuan)
        .filter((satuan): satuan is string => Boolean(satuan));
});

/** Sewa per hari wajib melampirkan surat permohonan. */
const isPerHari = computed(() => {
    if (!selectedSatuans.value.length) {
        return false;
    }

    return selectedSatuans.value.some((satuan) => !SATUAN_PER_JAM.includes(satuan));
});

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
            is_per_hari: isPerHari.value ? '1' : '0',
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
            is_per_hari: isPerHari.value ? '1' : '0',
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
            tone: 'text-(--sb-warning)',
        };
    }

    const map: Record<string, { label: string; detail: string; tone: string }> = {
        hijau: {
            label: 'Masih tersedia',
            detail: 'Waktu ini bisa diajukan.',
            tone: 'text-(--wp-accent-strong)',
        },
        merah: {
            label: 'Belum bisa dipakai',
            detail: 'Pilih waktu lain yang masih tersedia.',
            tone: 'text-(--sb-danger)',
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
        (!isPerHari.value || !!form.surat_permohonan) &&
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
    if (isPerHari.value && !form.surat_permohonan) {
        return 'Unggah surat permohonan (PDF, maks 5MB) untuk sewa per hari.';
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
            surat_permohonan: form.surat_permohonan,
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

const onSuratPermohonanChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    form.surat_permohonan = input.files?.[0] ?? null;
};

const slotClass = (slot: DaySlot) => {
    const selected = selectedSlotKey.value === slotKey(slot);
    if (selected) {
        return 'bg-(--wp-accent) text-(--wp-accent-contrast)';
    }
    if (slot.status === 'hijau' && slot.bookable) {
        return 'bg-card text-foreground ring-1 ring-(--wp-hairline) ring-inset hover:bg-(--wp-accent-soft) hover:text-(--wp-accent-strong)';
    }

    return 'cursor-not-allowed bg-muted text-muted-foreground';
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
        <div v-if="flashError" class="sb-callout sb-tone-danger mb-6" role="alert">
            <FontAwesomeIcon :icon="['fas', 'circle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p>{{ flashError }}</p>
        </div>

        <nav aria-label="Breadcrumb">
            <ol class="text-muted-foreground flex min-w-0 flex-wrap items-center gap-2 text-sm">
                <li>
                    <Link
                        :href="route('e-booking.catalog')"
                        class="inline-flex items-center gap-1.5 rounded-md font-medium transition hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                    >
                        <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
                        Katalog venue
                    </Link>
                </li>
                <li aria-hidden="true">/</li>
                <li class="text-foreground min-w-0 truncate" aria-current="page">{{ venue.name }}</li>
            </ol>
        </nav>

        <section aria-labelledby="venue-judul" class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:gap-12">
            <div class="order-2 lg:order-1 lg:py-4">
                <p class="wp-eyebrow">Venue Pemkab Bogor</p>
                <h1 id="venue-judul" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ venue.name }}</h1>

                <ul class="text-muted-foreground mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                    <li class="inline-flex items-center gap-1.5">
                        <FontAwesomeIcon :icon="['fas', 'hashtag']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        <span class="sr-only">Kode venue</span>
                        <span class="tabular-nums">{{ venue.code }}</span>
                    </li>
                    <li v-if="venue.areas.length" class="inline-flex items-center gap-1.5">
                        <FontAwesomeIcon :icon="['fas', 'layer-group']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        <span class="tabular-nums">{{ venue.areas.length }}</span> area
                    </li>
                    <li v-if="venue.facilities && venue.facilities.length" class="inline-flex items-center gap-1.5">
                        <FontAwesomeIcon :icon="['fas', 'check']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        <span class="tabular-nums">{{ venue.facilities.length }}</span> fasilitas
                    </li>
                </ul>

                <p v-if="venue.description" class="text-muted-foreground mt-5 max-w-prose text-sm leading-relaxed">{{ venue.description }}</p>

                <div v-if="venue.facilities && venue.facilities.length" class="mt-8">
                    <h2 class="text-sm font-semibold tracking-tight">Fasilitas</h2>
                    <ul class="mt-3 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                        <li v-for="facility in venue.facilities" :key="facility.id" class="flex items-center gap-3 text-sm">
                            <span class="wp-icon size-9 shrink-0">
                                <FacilityIcon :icon="facility.icon" class="size-4 text-(--wp-accent)" />
                            </span>
                            {{ facility.name }}
                        </li>
                    </ul>
                </div>

                <div v-if="areasWithPhoto.length" class="mt-8">
                    <h2 id="area-venue" class="text-sm font-semibold tracking-tight">Foto area</h2>
                    <p class="text-muted-foreground mt-1 text-xs">Klik foto untuk melihat lokasi area yang akan disewa.</p>
                    <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3" aria-labelledby="area-venue">
                        <li v-for="area in areasWithPhoto" :key="area.id">
                            <button
                                type="button"
                                class="group block w-full overflow-hidden rounded-xl text-left ring-1 ring-(--wp-hairline) transition hover:ring-(--wp-accent) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                :aria-label="`Lihat foto ${area.name}`"
                                @click="openAreaPreview(area)"
                            >
                                <span class="bg-muted relative block aspect-[4/3] overflow-hidden">
                                    <AppImage
                                        :src="area.photo_url!"
                                        :alt="area.name"
                                        class="size-full object-cover transition duration-300 group-hover:scale-105"
                                    />
                                </span>
                                <span class="flex items-center justify-between gap-2 px-3 py-2">
                                    <span class="truncate text-xs font-semibold">{{ area.name }}</span>
                                    <span v-if="area.is_tentative" class="sb-badge sb-tone-warning shrink-0 text-[10px]">Tentatif</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="order-1 space-y-4 lg:order-2">
                <div class="bg-muted relative aspect-[4/3] overflow-hidden rounded-2xl">
                    <AppImage v-if="venue.cover_url" :src="venue.cover_url" :alt="venue.name" class="size-full object-cover" />
                    <div v-else class="flex size-full items-center justify-center">
                        <span class="wp-icon size-14">
                            <FontAwesomeIcon :icon="['fas', 'image']" class="size-6" aria-hidden="true" />
                        </span>
                        <span class="sr-only">Foto venue belum tersedia</span>
                    </div>
                </div>

                <div v-if="termsPoints.length" id="tata-tertib" class="sb-card-muted scroll-mt-28 p-5">
                    <div class="flex items-start gap-3">
                        <span class="wp-icon size-9 shrink-0">
                            <FontAwesomeIcon :icon="['fas', 'shield-halved']" class="size-4" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold tracking-tight">{{ termsTitle || 'Tata tertib' }}</h2>
                            <ul class="text-muted-foreground mt-2 list-disc space-y-1.5 pl-5 text-sm leading-relaxed marker:text-(--wp-accent)">
                                <li v-for="(point, index) in termsPoints" :key="index">{{ point }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-12 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-10 xl:grid-cols-[minmax(0,1fr)_24rem]">
            <div class="space-y-6">
                <section aria-labelledby="langkah-area" class="sb-card p-5 sm:p-7">
                    <header class="mb-6 flex items-start gap-4">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--wp-accent) text-sm font-semibold text-(--wp-accent-contrast) tabular-nums"
                            aria-hidden="true"
                            >1</span
                        >
                        <div>
                            <h2 id="langkah-area" class="text-lg font-semibold tracking-tight">
                                <span class="sr-only">Langkah 1: </span>Pilih area & jenis sewa
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm leading-relaxed">Sewa seluruh venue, atau pilih beberapa area sekaligus.</p>
                        </div>
                    </header>

                    <div v-if="allAreasShortcut" class="sb-callout sb-tone-info mb-5">
                        <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <p class="text-xs leading-relaxed">
                            Seluruh area venue ini dipilih otomatis karena belum ada tarif khusus "semua area". Harga dihitung per area — silakan
                            sesuaikan jenis sewa tiap area bila perlu.
                        </p>
                    </div>

                    <template v-if="bookingMode === 'all'">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <p class="sb-label">Jenis sewa (seluruh venue)</p>
                                <SimpleSelect
                                    v-model="allTarifId"
                                    :options="tarifSelectOptions"
                                    placeholder="Pilih jenis sewa"
                                    required
                                    :trigger-class="selectTriggerClass"
                                />
                            </div>
                            <div>
                                <p class="sb-label">Jenis pemohon</p>
                                <SimpleSelect
                                    v-model="form.kategori_tarif"
                                    :options="kategoriSelectOptions"
                                    placeholder="Pilih jenis pemohon"
                                    :trigger-class="selectTriggerClass"
                                />
                                <p class="sb-hint">Harga menyesuaikan jenis pemohon.</p>
                            </div>
                            <div v-if="selectedTarif && needsQty(selectedTarif.satuan)">
                                <label for="booking-qty" class="sb-label">{{ qtyLabel }}</label>
                                <input id="booking-qty" v-model.number="form.qty" type="number" min="1" class="sb-input tabular-nums" />
                            </div>
                            <div v-if="selectedTarif && needsLuas(selectedTarif.satuan)">
                                <label for="booking-luas" class="sb-label">Luas area (m²)</label>
                                <input
                                    id="booking-luas"
                                    v-model.number="form.luas_m2"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    class="sb-input tabular-nums"
                                />
                            </div>
                        </div>

                        <div v-if="selectedTarif" class="sb-card-muted mt-5 p-4 text-sm">
                            <p class="font-semibold">{{ selectedTarif.uraian }}</p>
                            <p class="text-muted-foreground mt-1">Perhitungan: {{ satuanLabel(selectedTarif.satuan) }}</p>
                            <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs">
                                <div v-if="selectedTarif.tarif_pemerintah != null">
                                    <dt class="text-muted-foreground">Instansi pemerintah</dt>
                                    <dd class="mt-0.5 text-sm font-semibold tabular-nums">{{ formatRp(selectedTarif.tarif_pemerintah) }}</dd>
                                </div>
                                <div v-if="selectedTarif.tarif_non_pemerintah != null">
                                    <dt class="text-muted-foreground">Umum / non-pemerintah</dt>
                                    <dd class="mt-0.5 text-sm font-semibold tabular-nums">{{ formatRp(selectedTarif.tarif_non_pemerintah) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </template>

                    <template v-else>
                        <div class="space-y-3">
                            <div v-for="row in areaRows" :key="row.uid" class="sb-card-muted p-4">
                                <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                                    <div>
                                        <p class="sb-label">Area</p>
                                        <SimpleSelect
                                            :model-value="row.area_id"
                                            :options="areaRowOptions(row)"
                                            placeholder="Pilih area"
                                            :trigger-class="selectTriggerClass"
                                            @update:model-value="
                                                (val: string | number) => {
                                                    row.area_id = val === '' ? '' : Number(val);
                                                    onRowAreaChange(row);
                                                }
                                            "
                                        />
                                    </div>
                                    <div>
                                        <p class="sb-label">Jenis sewa</p>
                                        <SimpleSelect
                                            :model-value="row.tarif_id"
                                            :options="tarifRowOptions(row)"
                                            placeholder="Pilih jenis sewa"
                                            :trigger-class="selectTriggerClass"
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
                                        class="bg-card text-muted-foreground inline-flex size-11 items-center justify-center rounded-xl ring-1 ring-(--wp-hairline) transition ring-inset hover:text-(--sb-danger) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                        title="Hapus area ini"
                                        aria-label="Hapus area ini"
                                        @click="removeAreaRow(row.uid)"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'xmark']" class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                                <button
                                    v-if="areaById(row.area_id)?.photo_url"
                                    type="button"
                                    class="bg-card mt-3 flex w-full items-center gap-3 rounded-xl p-2 text-left ring-1 ring-(--wp-hairline) transition hover:ring-(--wp-accent) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                    @click="openAreaPreview(areaById(row.area_id))"
                                >
                                    <AppImage :src="areaById(row.area_id)!.photo_url!" alt="" class="h-12 w-16 shrink-0 rounded-lg object-cover" />
                                    <span class="min-w-0 text-xs">
                                        <span class="block truncate font-semibold">{{ areaById(row.area_id)!.name }}</span>
                                        <span class="font-medium text-(--wp-accent-strong)">Lihat foto area</span>
                                    </span>
                                </button>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div v-if="tarifForRow(row) && needsQty(tarifForRow(row)!.satuan)">
                                        <label :for="`row-qty-${row.uid}`" class="sb-label">{{ qtyLabel }}</label>
                                        <input
                                            :id="`row-qty-${row.uid}`"
                                            v-model.number="row.qty"
                                            type="number"
                                            min="1"
                                            class="sb-input tabular-nums"
                                        />
                                    </div>
                                    <div v-if="tarifForRow(row) && needsLuas(tarifForRow(row)!.satuan)">
                                        <label :for="`row-luas-${row.uid}`" class="sb-label">Luas area (m²)</label>
                                        <input
                                            :id="`row-luas-${row.uid}`"
                                            v-model.number="row.luas_m2"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            class="sb-input tabular-nums"
                                        />
                                    </div>
                                </div>
                            </div>

                            <button v-if="canAddAreaRow" type="button" class="wp-btn wp-btn-quiet px-4 py-2 text-sm" @click="addAreaRow">
                                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                                Tambah area
                            </button>
                        </div>

                        <div class="mt-5">
                            <p class="sb-label">Jenis pemohon</p>
                            <SimpleSelect
                                v-model="form.kategori_tarif"
                                :options="kategoriSelectOptions"
                                placeholder="Pilih jenis pemohon"
                                :trigger-class="selectTriggerClass"
                            />
                            <p class="sb-hint">Berlaku untuk semua area — total harga dihitung per area.</p>
                        </div>
                    </template>
                </section>

                <section aria-labelledby="langkah-jadwal" class="sb-card p-5 sm:p-7">
                    <header class="mb-6 flex items-start gap-4">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--wp-accent) text-sm font-semibold text-(--wp-accent-contrast) tabular-nums"
                            aria-hidden="true"
                            >2</span
                        >
                        <div>
                            <h2 id="langkah-jadwal" class="text-lg font-semibold tracking-tight">
                                <span class="sr-only">Langkah 2: </span>{{ scheduleTitle }}
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm leading-relaxed">{{ scheduleHint }}</p>
                        </div>
                    </header>

                    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                        <div>
                            <p class="sb-label">Kalender ketersediaan</p>
                            <BookingAvailabilityCalendar
                                v-model="selectedDate"
                                :venue-id="venue.id"
                                :area-ids="selectedAreaIds"
                                :is-per-hari="isPerHari"
                            />
                        </div>

                        <div class="space-y-5">
                            <div>
                                <p class="sb-label">
                                    {{ scheduleMode === 'monthly' ? 'Mulai dari tanggal' : 'Tanggal terpilih' }}
                                </p>
                                <p class="bg-muted/60 flex items-center gap-2.5 rounded-xl px-3.5 py-3 text-sm font-semibold tabular-nums">
                                    <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-4 text-(--wp-accent)" aria-hidden="true" />
                                    {{ selectedDate ? formatTanggalIndo(selectedDate) : 'Belum dipilih' }}
                                </p>
                            </div>

                            <div v-if="scheduleMode === 'hourly'">
                                <p class="sb-label">Lama pakai</p>
                                <SimpleSelect
                                    v-model="durationHours"
                                    :options="durationHourOptions"
                                    placeholder="Pilih lama"
                                    :trigger-class="selectTriggerClass"
                                />
                            </div>

                            <div v-else-if="scheduleMode === 'block3'">
                                <p class="sb-label">Jumlah blok</p>
                                <SimpleSelect
                                    v-model="durationBlocks"
                                    :options="durationBlockSelectOptions"
                                    placeholder="Pilih blok"
                                    :trigger-class="selectTriggerClass"
                                />
                            </div>

                            <div v-else-if="scheduleMode === 'daily'">
                                <p class="sb-label">Lama hari</p>
                                <SimpleSelect
                                    v-model="durationDays"
                                    :options="durationDaySelectOptions"
                                    placeholder="Pilih lama hari"
                                    :trigger-class="selectTriggerClass"
                                />
                            </div>

                            <div v-else-if="scheduleMode === 'monthly'">
                                <p class="sb-label">Lama bulan</p>
                                <SimpleSelect
                                    v-model="durationMonths"
                                    :options="durationMonthSelectOptions"
                                    placeholder="Pilih lama bulan"
                                    :trigger-class="selectTriggerClass"
                                />
                            </div>

                            <p v-else class="bg-muted/60 text-muted-foreground rounded-xl px-3.5 py-3 text-sm leading-relaxed">
                                Sewa dihitung per kegiatan / kunjungan pada tanggal terpilih.
                            </p>
                        </div>
                    </div>

                    <!-- Slot jam: hanya untuk tarif per jam / blok 3 jam -->
                    <div v-if="usesHourSlots" class="mt-7 border-t border-(--wp-hairline) pt-6">
                        <h3 class="mb-3 text-sm font-semibold tracking-tight">Pilih jam</h3>
                        <div v-if="slotsLoading" class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4" aria-busy="true">
                            <span class="sr-only">Memuat jadwal…</span>
                            <Skeleton v-for="n in 8" :key="n" class="h-16 rounded-xl" />
                        </div>
                        <p v-else-if="slotsError" class="sb-callout sb-tone-danger" role="alert">{{ slotsError }}</p>
                        <div v-else-if="dayFullDay" class="sb-callout sb-tone-danger">
                            <FontAwesomeIcon :icon="['fas', 'circle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <div>
                                <p class="font-semibold">Tanggal penuh</p>
                                <p class="mt-1">{{ dayFullDayReason || 'Ditutup pengelola' }} — tanggal ini tidak bisa dipesan.</p>
                            </div>
                        </div>
                        <template v-else>
                            <p v-if="dayPartialNotes.length" class="sb-callout sb-tone-warning mb-3 text-xs">
                                Sebagian jam ditutup: {{ dayPartialNotes.join(', ') }}
                            </p>
                            <div v-if="daySlots.length === 0" class="bg-muted/60 flex items-center gap-3 rounded-xl p-4">
                                <span class="wp-icon size-9 shrink-0">
                                    <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-4" aria-hidden="true" />
                                </span>
                                <p class="text-muted-foreground text-sm">Belum ada jam yang bisa dipilih untuk tanggal ini.</p>
                            </div>
                            <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                                <button
                                    v-for="slot in daySlots"
                                    :key="slotKey(slot)"
                                    type="button"
                                    class="relative rounded-xl px-3 py-3 text-left text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                    :class="slotClass(slot)"
                                    :disabled="!slot.bookable"
                                    :title="slotTitle(slot)"
                                    :aria-pressed="selectedSlotKey === slotKey(slot)"
                                    @click="selectSlot(slot)"
                                >
                                    <span class="block tabular-nums" :class="{ 'line-through': !slot.bookable }">{{ slot.label }}</span>
                                    <span class="mt-1 flex items-center gap-1.5 text-xs font-medium opacity-80">
                                        <span
                                            v-if="slot.bookable && slot.pengajuan && selectedSlotKey !== slotKey(slot)"
                                            class="size-1.5 shrink-0 rounded-full bg-(--sb-warning)"
                                            aria-hidden="true"
                                        />
                                        <template v-if="slot.bookable && slot.pengajuan">Ada pengajuan</template>
                                        <template v-else-if="slot.bookable">Tersedia</template>
                                        <template v-else>{{ slot.reason || 'Penuh' }}</template>
                                    </span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Ringkas periode untuk tarif harian / bulanan / kegiatan -->
                    <div v-else class="sb-card-muted mt-7 p-4 text-sm" aria-live="polite">
                        <template v-if="quoteLoading">
                            <span class="text-muted-foreground inline-flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                                Mengecek ketersediaan…
                            </span>
                        </template>
                        <template v-else-if="form.starts_at && form.ends_at">
                            <p class="font-semibold">Periode terpilih</p>
                            <p class="mt-1 tabular-nums">
                                {{ selectedDate ? formatTanggalIndo(selectedDate) : '' }}
                                <template v-if="endDate && endDate !== selectedDate"> — {{ formatTanggalIndo(endDate) }}</template>
                            </p>
                            <p class="text-muted-foreground mt-1 text-xs tabular-nums">
                                Jam operasional {{ operatingHours.start }}–{{ operatingHours.end }}
                            </p>
                        </template>
                        <template v-else>
                            <span class="text-muted-foreground">Pilih tanggal di atas. Harga dan ketersediaan dihitung otomatis.</span>
                        </template>
                    </div>

                    <InputError :message="form.errors.starts_at || form.errors.ends_at" />
                </section>

                <section aria-labelledby="langkah-data" class="sb-card p-5 sm:p-7">
                    <header class="mb-6 flex items-start gap-4">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-(--wp-accent) text-sm font-semibold text-(--wp-accent-contrast) tabular-nums"
                            aria-hidden="true"
                            >3</span
                        >
                        <div>
                            <h2 id="langkah-data" class="text-lg font-semibold tracking-tight">
                                <span class="sr-only">Langkah 3: </span>Lengkapi pengajuan
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm leading-relaxed">Isi keperluan dan tambahan layanan bila perlu.</p>
                        </div>
                    </header>

                    <div class="sb-callout sb-tone-info mb-6">
                        <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <p class="text-xs leading-relaxed">
                            Pengajuan diproses maksimal <span class="tabular-nums">{{ slaHariKerja }}</span> hari kerja. Setelah disetujui, selesaikan
                            pembayaran sebelum batas waktu — jika terlewat, booking otomatis batal dan harus diajukan ulang.
                        </p>
                    </div>

                    <div>
                        <label for="booking-tujuan" class="sb-label">Dipakai untuk apa?</label>
                        <input
                            id="booking-tujuan"
                            v-model="form.tujuan"
                            type="text"
                            required
                            maxlength="255"
                            class="sb-input"
                            :aria-invalid="form.errors.tujuan ? 'true' : undefined"
                            placeholder="Contoh: latihan klub, sparring, kegiatan komunitas"
                        />
                        <InputError :message="form.errors.tujuan" />
                    </div>

                    <fieldset v-if="addons.length" class="mt-6">
                        <legend class="sb-label">Tambahan layanan (opsional)</legend>
                        <div class="mt-1 grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="addon in addons"
                                :key="addon.id"
                                type="button"
                                class="flex items-start justify-between gap-3 rounded-xl p-4 text-left text-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                :class="
                                    selectedAddonIds.includes(addon.id)
                                        ? 'bg-(--wp-accent-soft) ring-2 ring-(--wp-accent) ring-inset'
                                        : 'bg-card hover:bg-muted/60 ring-1 ring-(--wp-hairline) ring-inset'
                                "
                                :aria-pressed="selectedAddonIds.includes(addon.id)"
                                @click="toggleAddon(addon.id)"
                            >
                                <span>
                                    <span
                                        class="block font-medium"
                                        :class="selectedAddonIds.includes(addon.id) ? 'text-(--wp-accent-strong)' : 'text-foreground'"
                                    >
                                        {{ addon.name }}
                                    </span>
                                    <span v-if="addon.harga != null" class="text-muted-foreground mt-1 block text-xs font-semibold tabular-nums">
                                        {{ formatRp(addon.harga) }}
                                    </span>
                                </span>
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-md transition"
                                    :class="
                                        selectedAddonIds.includes(addon.id)
                                            ? 'bg-(--wp-accent) text-(--wp-accent-contrast)'
                                            : 'ring-1 ring-(--wp-hairline) ring-inset'
                                    "
                                    aria-hidden="true"
                                >
                                    <FontAwesomeIcon v-if="selectedAddonIds.includes(addon.id)" :icon="['fas', 'check']" class="size-3" />
                                </span>
                            </button>
                        </div>
                    </fieldset>
                </section>
            </div>

            <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start" aria-labelledby="ringkasan-judul">
                <section class="sb-card p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 id="ringkasan-judul" class="text-lg font-semibold tracking-tight">Ringkasan</h2>
                            <p class="text-muted-foreground mt-1 text-sm">Cek dulu sebelum mengirim.</p>
                        </div>
                        <span class="sb-badge sb-tone-success max-w-40 truncate" :title="selectedAreaName">
                            {{ selectedAreaName }}
                        </span>
                    </div>

                    <dl class="mt-5 divide-y divide-(--wp-hairline) text-sm">
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-muted-foreground shrink-0">Tempat</dt>
                            <dd class="text-right font-medium">{{ venue.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-muted-foreground shrink-0">Area</dt>
                            <dd class="text-right">{{ selectedAreaName }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-muted-foreground shrink-0">Jenis sewa</dt>
                            <dd class="text-right">{{ selectedTarifNames }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-muted-foreground shrink-0">Jenis pemohon</dt>
                            <dd class="text-right">{{ jenisPemohonLabel(form.kategori_tarif) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-muted-foreground shrink-0">Jadwal</dt>
                            <dd class="text-right tabular-nums">
                                <template v-if="form.starts_at && form.ends_at">
                                    <template v-if="usesHourSlots">
                                        {{ formatTanggalJamIndo(form.starts_at) }} — {{ formatJamIndo(form.ends_at) }}
                                    </template>
                                    <template v-else>
                                        {{ selectedDate ? formatTanggalIndo(selectedDate) : '' }}
                                        <template v-if="endDate && endDate !== selectedDate"> — {{ formatTanggalIndo(endDate) }}</template>
                                    </template>
                                </template>
                                <span v-else class="text-muted-foreground">Belum dipilih</span>
                            </dd>
                        </div>
                    </dl>

                    <div
                        v-if="localAvailability && availabilityCopy"
                        class="sb-callout mt-4"
                        :class="{
                            'sb-tone-success': localAvailability.status === 'hijau' && !localAvailability.pengajuan,
                            'sb-tone-warning': localAvailability.status === 'hijau' && localAvailability.pengajuan,
                            'sb-tone-danger': localAvailability.status === 'merah',
                        }"
                        role="status"
                    >
                        <div>
                            <p class="font-semibold" :class="availabilityCopy.tone">{{ availabilityCopy.label }}</p>
                            <p class="text-foreground/80 mt-1">{{ availabilityCopy.detail }}</p>
                        </div>
                    </div>

                    <div v-if="quoteLoading" class="bg-muted/60 mt-5 space-y-3 rounded-2xl p-4" aria-busy="true">
                        <span class="sr-only">Menghitung harga…</span>
                        <div class="flex justify-between gap-3">
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-4 w-20" />
                        </div>
                        <div class="flex justify-between gap-3">
                            <Skeleton class="h-4 w-24" />
                            <Skeleton class="h-4 w-16" />
                        </div>
                        <div class="flex justify-between gap-3 border-t border-(--wp-hairline) pt-3">
                            <Skeleton class="h-5 w-28" />
                            <Skeleton class="h-5 w-24" />
                        </div>
                    </div>
                    <p v-else-if="quoteError" class="sb-callout sb-tone-danger mt-5" role="alert">{{ quoteError }}</p>
                    <div v-else-if="localQuote" class="bg-muted/60 mt-5 rounded-2xl p-4">
                        <h3 class="text-muted-foreground text-xs font-medium">Rincian harga</h3>
                        <dl class="mt-3 space-y-2.5 text-sm">
                            <div v-for="(line, index) in localQuote.lines" :key="`line-${index}`" class="flex justify-between gap-3">
                                <dt class="text-muted-foreground">
                                    {{ line.uraian }}<template v-if="line.area"> · {{ line.area.name }}</template>
                                    <span v-if="line.duration_label" class="block text-xs tabular-nums">{{ line.duration_label }}</span>
                                </dt>
                                <dd class="shrink-0 font-medium tabular-nums">{{ formatRp(line.line_total) }}</dd>
                            </div>
                            <div
                                v-for="(addon, index) in localQuote.addons ?? []"
                                :key="`addon-${index}`"
                                class="flex justify-between gap-3"
                                :class="index === 0 ? 'border-t border-(--wp-hairline) pt-2.5' : ''"
                            >
                                <dt class="text-muted-foreground">{{ addon.name }}</dt>
                                <dd class="shrink-0 font-medium tabular-nums">{{ formatRp(addon.line_total) }}</dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-3 border-t border-(--wp-hairline) pt-3">
                                <dt class="font-semibold">Perkiraan total</dt>
                                <dd class="text-xl font-bold tracking-tight tabular-nums">{{ formatRp(localQuote.grand_total) }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div v-else class="bg-muted/60 mt-5 flex items-center gap-3 rounded-2xl p-4">
                        <span class="wp-icon size-9 shrink-0">
                            <FontAwesomeIcon :icon="['fas', 'wallet']" class="size-4" aria-hidden="true" />
                        </span>
                        <p class="text-muted-foreground text-sm">
                            {{
                                usesHourSlots
                                    ? 'Pilih jam di sebelah kiri untuk melihat harga.'
                                    : 'Pilih tanggal di sebelah kiri untuk melihat harga.'
                            }}
                        </p>
                    </div>

                    <label class="hover:bg-muted/60 mt-5 flex cursor-pointer items-start gap-3 rounded-xl p-3 text-sm transition">
                        <input v-model="form.tata_tertib_accepted" type="checkbox" class="mt-0.5 size-4 shrink-0 accent-(--wp-accent)" />
                        <span class="text-muted-foreground leading-relaxed">
                            Saya sudah membaca
                            <a
                                v-if="termsPoints.length"
                                href="#tata-tertib"
                                class="rounded-sm font-semibold text-(--wp-accent-strong) underline underline-offset-2 hover:text-(--wp-accent) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                            >
                                tata tertib
                            </a>
                            <template v-else>tata tertib</template>
                            di atas dan akan mematuhinya.
                        </span>
                    </label>
                    <InputError :message="form.errors.tata_tertib_accepted" />

                    <label class="hover:bg-muted/60 mt-1 flex cursor-pointer items-start gap-3 rounded-xl p-3 text-sm transition">
                        <input v-model="form.terms_accepted" type="checkbox" class="mt-0.5 size-4 shrink-0 accent-(--wp-accent)" />
                        <span class="text-muted-foreground leading-relaxed">{{ termsText }}</span>
                    </label>
                    <InputError :message="form.errors.terms_accepted" />

                    <div v-if="isPerHari" class="mt-4 rounded-xl border border-(--wp-hairline) p-4">
                        <label for="surat-permohonan" class="text-sm font-semibold">
                            Surat permohonan <span class="text-(--sb-danger)">*</span>
                        </label>
                        <p class="text-muted-foreground mt-1 text-xs">Sewa per hari wajib melampirkan surat permohonan (PDF, maks 5MB).</p>
                        <input
                            id="surat-permohonan"
                            type="file"
                            accept="application/pdf,.pdf"
                            class="mt-3 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-(--wp-accent-soft) file:px-3 file:py-2 file:text-sm file:font-medium file:text-(--wp-accent-strong)"
                            @change="onSuratPermohonanChange"
                        />
                        <p v-if="form.surat_permohonan" class="text-muted-foreground mt-2 text-xs">Terpilih: {{ form.surat_permohonan.name }}</p>
                        <InputError :message="form.errors.surat_permohonan" class="mt-1" />
                    </div>

                    <button
                        v-if="bookingAuth"
                        type="button"
                        class="wp-btn wp-btn-primary mt-5 w-full justify-center px-5 py-3 text-sm"
                        :disabled="!canSubmit"
                        @click="submitBooking"
                    >
                        <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                        <FontAwesomeIcon v-else :icon="['fas', 'paper-plane']" class="size-4" aria-hidden="true" />
                        Kirim pengajuan
                    </button>
                    <p v-if="bookingAuth && submitHint" class="mt-2.5 text-center text-xs text-(--sb-warning)" aria-live="polite">
                        {{ submitHint }}
                    </p>

                    <div v-if="!bookingAuth" class="mt-5 space-y-2.5">
                        <Link :href="route('e-booking.login')" class="wp-btn wp-btn-primary w-full justify-center px-5 py-3 text-sm">
                            Masuk untuk lanjut
                        </Link>
                        <Link :href="route('e-booking.register')" class="wp-btn wp-btn-quiet w-full justify-center px-5 py-3 text-sm">
                            Buat akun baru
                        </Link>
                        <p class="text-muted-foreground text-center text-xs">Anda tetap bisa cek jadwal dan harga tanpa masuk.</p>
                    </div>
                </section>

                <section class="sb-card-muted p-5" aria-labelledby="setelah-dikirim">
                    <div class="flex items-start gap-3">
                        <span class="wp-icon size-9 shrink-0">
                            <FontAwesomeIcon :icon="['fas', 'circle-exclamation']" class="size-4" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 id="setelah-dikirim" class="text-sm font-semibold tracking-tight">Setelah dikirim</h2>
                            <p class="text-muted-foreground mt-1 text-sm leading-relaxed">
                                Pengelola akan meninjau. Jika disetujui, petunjuk pembayaran muncul di halaman pesanan.
                            </p>
                        </div>
                    </div>

                    <ul class="text-muted-foreground mt-4 space-y-2.5 border-t border-(--wp-hairline) pt-4 text-sm">
                        <li class="flex items-start gap-2.5">
                            <FontAwesomeIcon
                                :icon="['fas', 'calendar-days']"
                                class="mt-0.5 size-3.5 shrink-0 text-(--wp-accent)"
                                aria-hidden="true"
                            />
                            {{ usesHourSlots ? 'Pilih jam dari daftar yang tersedia' : 'Jadwal menyesuaikan satuan tarif (hari / bulan / kegiatan)' }}
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FontAwesomeIcon :icon="['fas', 'wallet']" class="mt-0.5 size-3.5 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                            Harga muncul otomatis setelah jadwal dipilih
                        </li>
                        <li class="flex items-start gap-2.5">
                            <FontAwesomeIcon
                                :icon="['fas', 'shield-halved']"
                                class="mt-0.5 size-3.5 shrink-0 text-(--wp-accent)"
                                aria-hidden="true"
                            />
                            Kirim pengajuan hanya jika jadwal masih terbuka
                        </li>
                    </ul>
                </section>
            </aside>
        </div>

        <Dialog
            :open="previewArea !== null"
            @update:open="
                (value: boolean) => {
                    if (!value) previewArea = null;
                }
            "
        >
            <DialogContent class="bg-card overflow-hidden rounded-3xl border-(--wp-hairline) p-0 sm:max-w-3xl">
                <AppImage
                    v-if="previewArea?.photo_url"
                    :src="previewArea.photo_url"
                    :alt="previewArea.name"
                    :lazy="false"
                    class="max-h-[70vh] w-full bg-black/5 object-contain"
                />
                <DialogHeader class="gap-1 px-6 pt-1 pb-6">
                    <DialogTitle class="text-foreground text-lg font-semibold tracking-tight">{{ previewArea?.name }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm">
                        {{ venue.name }}<template v-if="previewArea?.is_tentative"> · Area tentatif</template>
                    </DialogDescription>
                </DialogHeader>
            </DialogContent>
        </Dialog>
    </EBookingLayout>
</template>
