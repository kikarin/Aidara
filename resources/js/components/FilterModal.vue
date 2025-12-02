<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from 'axios';
import { CalendarIcon } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

interface FilterData {
    filter_start_date?: string;
    filter_end_date?: string;
    cabor_id?: string;
    cabor_kategori_id?: string;
    jenis_kelamin?: string;
    kategori_usia?: string;
    lama_bergabung?: string;
    kategori_atlet_id?: string;
    kategori_peserta_id?: string;
    [key: string]: any;
}

interface Props {
    open: boolean;
    moduleType: 'cabor-kategori' | 'program-latihan' | 'pemeriksaan' | 'atlet' | 'pelatih' | 'tenaga-pendukung';
    initialFilters?: FilterData;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'filter', filters: FilterData): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const filters = ref<FilterData>({
    filter_start_date: '',
    filter_end_date: '',
    cabor_id: 'all',
    cabor_kategori_id: 'all',
    jenis_kelamin: 'all',
    kategori_usia: 'all',
    lama_bergabung: 'all',
    kategori_atlet_id: 'all',
});

const cabors = ref<Array<{ id: number; nama: string }>>([]);
const caborKategoris = ref<Array<{ id: number; nama: string }>>([]);
const loadingCaborKategoris = ref(false);
const kategoriAtlets = ref<Array<{ id: number; nama: string }>>([]);

// State untuk search query per select field
const selectSearchQuery = ref<Record<string, string>>({});

// Load cabors and kategori atlets on mount
onMounted(async () => {
    try {
        const response = await axios.get('/api/cabor-list');
        cabors.value = response.data;
    } catch (error) {
        console.error('Gagal load cabor:', error);
    }

    // Load kategori peserta for all peserta modules (atlet, pelatih, tenaga-pendukung)
    if (['atlet', 'pelatih', 'tenaga-pendukung'].includes(props.moduleType)) {
        try {
            const kategoriPesertaResponse = await axios.get('/api/kategori-peserta-list');
            kategoriAtlets.value = kategoriPesertaResponse.data;
        } catch (error) {
            console.error('Gagal load kategori peserta:', error);
        }
    }
});

// Watch cabor_id changes to load cabor_kategoris
watch(
    () => filters.value.cabor_id,
    async (newCaborId) => {
        if (newCaborId && newCaborId !== 'all') {
            loadingCaborKategoris.value = true;
            try {
                const response = await axios.get(`/api/cabor-kategori-by-cabor/${newCaborId}`);
                caborKategoris.value = response.data;
            } catch (error) {
                console.error('Gagal load cabor kategori:', error);
                caborKategoris.value = [];
            } finally {
                loadingCaborKategoris.value = false;
            }
        } else {
            caborKategoris.value = [];
            filters.value.cabor_kategori_id = 'all';
        }
    },
);

// Watch props.open to reset filters when modal opens
watch(
    () => props.open,
    (newOpen) => {
        if (newOpen) {
            if (props.initialFilters) {
                filters.value = { ...props.initialFilters };
            } else {
                // Set default values based on module type
                filters.value = {
                    filter_start_date: '',
                    filter_end_date: '',
                    cabor_id: 'all',
                    cabor_kategori_id: 'all',
                    jenis_kelamin: 'all',
                    kategori_usia: 'all',
                    lama_bergabung: 'all',
                    kategori_atlet_id: 'all',
                    kategori_peserta_id: 'all',
                };
            }
        }
    },
);

const handleFilter = () => {
    // Clean up empty values before emitting
    const cleanFilters: FilterData = {};
    Object.keys(filters.value).forEach((key) => {
        if (filters.value[key] && filters.value[key] !== 'all') {
            cleanFilters[key] = filters.value[key];
        }
    });

    // Map kategori_peserta_id to kategori_atlet_id for backward compatibility
    if (cleanFilters.kategori_peserta_id && !cleanFilters.kategori_atlet_id) {
        cleanFilters.kategori_atlet_id = cleanFilters.kategori_peserta_id;
    }

    // Debug logging untuk filter status
    console.log('Filter values before cleaning:', filters.value);
    console.log('Clean filters to be sent:', cleanFilters);

    emit('filter', cleanFilters);
    emit('update:open', false);
};

const resetFilters = () => {
    filters.value = {
        filter_start_date: '',
        filter_end_date: '',
        cabor_id: 'all',
        cabor_kategori_id: 'all',
        jenis_kelamin: 'all',
        kategori_usia: 'all',
        lama_bergabung: 'all',
        kategori_atlet_id: 'all',
        kategori_peserta_id: 'all',
    };
};

const getTitle = () => {
    switch (props.moduleType) {
        default:
            return 'Filter Data';
    }
};

// Method untuk filter options berdasarkan search query
const getFilteredOptions = (options: Array<{ id: number; nama: string }>, fieldName: string) => {
    const query = selectSearchQuery.value[fieldName] || '';
    if (!query) return options;
    return options.filter((opt: any) => (opt.nama || '').toLowerCase().includes(query.toLowerCase()));
};

const triggerDatePicker = (fieldName: string) => {
    const dateInput = document.getElementById(`${fieldName}-date-input`) as HTMLInputElement;
    if (dateInput && dateInput.showPicker) {
        dateInput.showPicker();
    }
};

// Options untuk filter peserta
const getJenisKelaminOptions = () => [
    { value: 'L', label: 'Laki-laki' },
    { value: 'P', label: 'Perempuan' },
];

const getKategoriUsiaOptions = () => {
    switch (props.moduleType) {
        case 'atlet':
            return [
                { value: 'anak', label: 'Anak-anak (0-12 tahun)' },
                { value: 'remaja', label: 'Remaja (13-17 tahun)' },
                { value: 'dewasa_muda', label: 'Dewasa Muda (18-25 tahun)' },
                { value: 'dewasa', label: 'Dewasa (26-35 tahun)' },
                { value: 'dewasa_tua', label: 'Dewasa Tua (36+ tahun)' },
            ];
        case 'pelatih':
        case 'tenaga-pendukung':
            return [
                { value: 'dewasa_muda', label: 'Dewasa Muda (18-25 tahun)' },
                { value: 'dewasa', label: 'Dewasa (26-35 tahun)' },
                { value: 'dewasa_tua', label: 'Dewasa Tua (36-45 tahun)' },
                { value: 'senior', label: 'Senior (46-55 tahun)' },
                { value: 'veteran', label: 'Veteran (56+ tahun)' },
            ];
        default:
            return [];
    }
};

const getLamaBergabungOptions = () => {
    switch (props.moduleType) {
        case 'atlet':
            return [
                { value: 'baru', label: 'Baru bergabung (< 1 tahun)' },
                { value: 'sedang', label: 'Sedang (1-3 tahun)' },
                { value: 'lama', label: 'Lama (3-5 tahun)' },
                { value: 'sangat_lama', label: 'Sangat lama (5+ tahun)' },
            ];
        case 'pelatih':
        case 'tenaga-pendukung':
            return [
                { value: 'baru', label: 'Baru bergabung (< 2 tahun)' },
                { value: 'sedang', label: 'Sedang (2-5 tahun)' },
                { value: 'lama', label: 'Lama (5-10 tahun)' },
                { value: 'sangat_lama', label: 'Sangat lama (10+ tahun)' },
            ];
        default:
            return [];
    }
};

// Computed properties untuk conditional rendering
const isPesertaModule = computed(() => ['atlet', 'pelatih', 'tenaga-pendukung'].includes(props.moduleType));
const isCaborModule = computed(() => ['cabor-kategori', 'program-latihan', 'pemeriksaan'].includes(props.moduleType));
</script>

<template>
    <Dialog :open="open" @update:open="(val: boolean) => emit('update:open', val)">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle>{{ getTitle() }}</DialogTitle>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <!-- Filter Tanggal -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="filter_start_date">Tanggal Mulai</Label>
                        <div class="relative">
                            <Input
                                :id="`filter_start_date-date-input`"
                                v-model="filters.filter_start_date"
                                type="date"
                                placeholder="Pilih tanggal mulai"
                                class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-inner-spin-button]:hidden [&::-webkit-outer-spin-button]:hidden"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                @click="() => triggerDatePicker('filter_start_date')"
                            >
                                <CalendarIcon class="text-muted-foreground h-4 w-4" />
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label for="filter_end_date">Tanggal Akhir</Label>
                        <div class="relative">
                            <Input
                                :id="`filter_end_date-date-input`"
                                v-model="filters.filter_end_date"
                                type="date"
                                placeholder="Pilih tanggal akhir"
                                class="pr-10 [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-inner-spin-button]:hidden [&::-webkit-outer-spin-button]:hidden"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3"
                                @click="() => triggerDatePicker('filter_end_date')"
                            >
                                <CalendarIcon class="text-muted-foreground h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter khusus untuk peserta (atlet, pelatih, tenaga-pendukung) -->
                <template v-if="isPesertaModule">
                    <!-- Filter Jenis Kelamin -->
                    <div class="space-y-2">
                        <Label for="jenis_kelamin">Jenis Kelamin</Label>
                        <Select v-model="filters.jenis_kelamin">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Jenis Kelamin" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Jenis Kelamin</SelectItem>
                                <SelectItem v-for="option in getJenisKelaminOptions()" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Kategori Usia -->
                    <div class="space-y-2">
                        <Label for="kategori_usia">Kategori Usia</Label>
                        <Select v-model="filters.kategori_usia">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Kategori Usia" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Kategori Usia</SelectItem>
                                <SelectItem v-for="option in getKategoriUsiaOptions()" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Lama Bergabung -->
                    <div class="space-y-2">
                        <Label for="lama_bergabung">Lama Bergabung</Label>
                        <Select v-model="filters.lama_bergabung">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Lama Bergabung" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Lama Bergabung</SelectItem>
                                <SelectItem v-for="option in getLamaBergabungOptions()" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Kategori Peserta -->
                    <div class="space-y-2">
                        <Label for="kategori_peserta_id">Kategori Peserta</Label>
                        <Select v-model="filters.kategori_peserta_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Kategori Peserta" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- Search input -->
                                <div class="p-2">
                                    <input
                                        v-model="selectSearchQuery.kategori_peserta_id"
                                        type="text"
                                        placeholder="Cari kategori peserta..."
                                        class="w-full rounded border px-2 py-1 text-sm"
                                        @click.stop
                                        @keydown.stop
                                    />
                                </div>
                                <!-- Filtered options -->
                                <SelectItem value="all">Semua Kategori Peserta</SelectItem>
                                <SelectItem
                                    v-for="kategori in getFilteredOptions(kategoriAtlets, 'kategori_peserta_id')"
                                    :key="kategori.id"
                                    :value="kategori.id.toString()"
                                >
                                    {{ kategori.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Cabor & Kategori (2 kolom) -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Cabor -->
                        <div class="space-y-2">
                            <Label for="cabor_id">Cabor</Label>
                            <Select v-model="filters.cabor_id">
                                <SelectTrigger class="w-full">
                                    <SelectValue placeholder="Pilih Cabor" />
                                </SelectTrigger>
                                <SelectContent>
                                    <!-- Search input -->
                                    <div class="p-2">
                                        <input
                                            v-model="selectSearchQuery.cabor_id"
                                            type="text"
                                            placeholder="Cari cabor..."
                                            class="w-full rounded border px-2 py-1 text-sm"
                                            @click.stop
                                            @keydown.stop
                                        />
                                    </div>
                                    <!-- Filtered options -->
                                    <SelectItem value="all">Semua Cabor</SelectItem>
                                    <SelectItem v-for="cabor in getFilteredOptions(cabors, 'cabor_id')" :key="cabor.id" :value="cabor.id.toString()">
                                        {{ cabor.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Kategori -->
                        <div class="space-y-2">
                            <Label for="cabor_kategori_id">Kategori</Label>
                            <Select
                                v-model="filters.cabor_kategori_id"
                                :disabled="!filters.cabor_id || filters.cabor_id === 'all' || loadingCaborKategoris"
                            >
                                <SelectTrigger class="w-full">
                                    <SelectValue :placeholder="loadingCaborKategoris ? 'Loading...' : 'Pilih Kategori'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <!-- Search input -->
                                    <div class="p-2">
                                        <input
                                            v-model="selectSearchQuery.cabor_kategori_id"
                                            type="text"
                                            placeholder="Cari kategori..."
                                            class="w-full rounded border px-2 py-1 text-sm"
                                            @click.stop
                                            @keydown.stop
                                        />
                                    </div>
                                    <!-- Filtered options -->
                                    <SelectItem value="all">Semua Kategori</SelectItem>
                                    <SelectItem
                                        v-for="kategori in getFilteredOptions(caborKategoris, 'cabor_kategori_id')"
                                        :key="kategori.id"
                                        :value="kategori.id.toString()"
                                    >
                                        {{ kategori.nama }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </template>

                <!-- Filter Cabor (hanya untuk cabor-kategori, program-latihan, pemeriksaan) -->
                <template v-if="isCaborModule">
                    <!-- Filter Cabor -->
                    <div class="space-y-2">
                        <Label for="cabor_id">Cabor</Label>
                        <Select v-model="filters.cabor_id">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Pilih Cabor" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- Search input -->
                                <div class="p-2">
                                    <input
                                        v-model="selectSearchQuery.cabor_id"
                                        type="text"
                                        placeholder="Cari cabor..."
                                        class="w-full rounded border px-2 py-1 text-sm"
                                        @click.stop
                                        @keydown.stop
                                    />
                                </div>
                                <!-- Filtered options -->
                                <SelectItem value="all">Semua Cabor</SelectItem>
                                <SelectItem v-for="cabor in getFilteredOptions(cabors, 'cabor_id')" :key="cabor.id" :value="cabor.id.toString()">
                                    {{ cabor.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Cabor Kategori (hanya untuk program-latihan dan pemeriksaan) -->
                    <div v-if="props.moduleType !== 'cabor-kategori'" class="space-y-2">
                        <Label for="cabor_kategori_id">Kategori</Label>
                        <Select
                            v-model="filters.cabor_kategori_id"
                            :disabled="!filters.cabor_id || filters.cabor_id === 'all' || loadingCaborKategoris"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue :placeholder="loadingCaborKategoris ? 'Loading...' : 'Pilih Kategori'" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- Search input -->
                                <div class="p-2">
                                    <input
                                        v-model="selectSearchQuery.cabor_kategori_id"
                                        type="text"
                                        placeholder="Cari kategori..."
                                        class="w-full rounded border px-2 py-1 text-sm"
                                        @click.stop
                                        @keydown.stop
                                    />
                                </div>
                                <!-- Filtered options -->
                                <SelectItem value="all">Semua Kategori</SelectItem>
                                <SelectItem
                                    v-for="kategori in getFilteredOptions(caborKategoris, 'cabor_kategori_id')"
                                    :key="kategori.id"
                                    :value="kategori.id.toString()"
                                >
                                    {{ kategori.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter khusus untuk cabor-kategori -->
                    <div v-if="props.moduleType === 'cabor-kategori'" class="space-y-2">
                        <Label for="cabor_kategori_id">Nama Kategori</Label>
                        <Select
                            v-model="filters.cabor_kategori_id"
                            :disabled="!filters.cabor_id || filters.cabor_id === 'all' || loadingCaborKategoris"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue :placeholder="loadingCaborKategoris ? 'Loading...' : 'Pilih Kategori'" />
                            </SelectTrigger>
                            <SelectContent>
                                <!-- Search input -->
                                <div class="p-2">
                                    <input
                                        v-model="selectSearchQuery.cabor_kategori_id"
                                        type="text"
                                        placeholder="Cari kategori..."
                                        class="w-full rounded border px-2 py-1 text-sm"
                                        @click.stop
                                        @keydown.stop
                                    />
                                </div>
                                <!-- Filtered options -->
                                <SelectItem value="all">Semua Kategori</SelectItem>
                                <SelectItem
                                    v-for="kategori in getFilteredOptions(caborKategoris, 'cabor_kategori_id')"
                                    :key="kategori.id"
                                    :value="kategori.id.toString()"
                                >
                                    {{ kategori.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="resetFilters"> Reset </Button>
                <Button @click="handleFilter"> Terapkan Filter </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
