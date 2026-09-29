<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import FormInput from '@/pages/modules/base-page/FormInput.vue';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

const { save } = useHandleFormSave();
const { toast } = useToast();

const props = defineProps<{
    mode: 'create' | 'edit';
    initialData?: Record<string, any>;
}>();

const caborOptions = ref<{ value: number; label: string }[]>([]);
const caborKategoriOptions = ref<{ value: number; label: string }[]>([]);
const pelatihOptions = ref<{ value: number; label: string }[]>([]);
const selectedCaborId = ref(props.initialData?.cabor_id || '');
const selectedKategoriId = ref(props.initialData?.cabor_kategori_id || '');
const modePelatih = ref<'single' | 'multiple'>(props.initialData?.mode_pelatih || 'single');
const selectedPelatihIds = ref<number[]>(
    (props.initialData?.pelatihs?.map((p: any) => Number(p.id)) ??
        (props.initialData?.pelatih_id ? [Number(props.initialData.pelatih_id)] : [])) as number[]
);
const wajibAbsenAtlet = ref(!!props.initialData?.wajib_absen_atlet);
const absenJamMulai = ref(
    props.initialData?.absen_jam_mulai ? String(props.initialData.absen_jam_mulai).slice(0, 5) : ''
);
const absenJamSelesai = ref(
    props.initialData?.absen_jam_selesai ? String(props.initialData.absen_jam_selesai).slice(0, 5) : ''
);

const fetchCaborOptions = async () => {
    const res = await axios.get('/api/cabor-list');
    caborOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

const fetchCaborKategoriOptions = async (caborId: number | string) => {
    if (!caborId) {
        caborKategoriOptions.value = [];
        return;
    }
    const res = await axios.get(`/api/cabor-kategori-by-cabor/${caborId}`);
    caborKategoriOptions.value = (res.data || []).map((item: any) => ({ value: item.id, label: item.nama }));
};

const fetchPelatihOptions = async (caborKategoriId: number | string, caborId?: number | string) => {
    if (!caborKategoriId) {
        pelatihOptions.value = [];
        return;
    }
    const res = await axios.get(`/api/program-latihan/pelatih-by-kategori/${caborKategoriId}`, {
        params: caborId ? { cabor_id: caborId } : undefined,
    });
    pelatihOptions.value = (res.data || []).map((item: any) => ({
        value: Number(item.id),
        label: item.jenis_pelatih ? `${item.nama} (${item.jenis_pelatih})` : item.nama,
    }));
};

onMounted(async () => {
    await fetchCaborOptions();
    if (selectedCaborId.value) {
        await fetchCaborKategoriOptions(selectedCaborId.value);
    }
    if (selectedKategoriId.value) {
        await fetchPelatihOptions(selectedKategoriId.value, selectedCaborId.value);
    }
});

watch(selectedCaborId, async (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedKategoriId.value = '';
        selectedPelatihIds.value = [];
        pelatihOptions.value = [];
        await fetchCaborKategoriOptions(newVal);
    }
});

watch(selectedKategoriId, async (newVal, oldVal) => {
    if (newVal !== oldVal) {
        selectedPelatihIds.value = [];
        await fetchPelatihOptions(newVal, selectedCaborId.value);
    }
});

watch(modePelatih, (mode) => {
    if (mode === 'single' && selectedPelatihIds.value.length > 1) {
        selectedPelatihIds.value = selectedPelatihIds.value.slice(0, 1);
    }
});

const togglePelatih = (id: number, checked: boolean | 'indeterminate') => {
    const pelatihId = Number(id);
    const isChecked = checked === true;

    if (modePelatih.value === 'single') {
        selectedPelatihIds.value = isChecked ? [pelatihId] : [];
        return;
    }

    if (isChecked) {
        if (!selectedPelatihIds.value.includes(pelatihId)) {
            selectedPelatihIds.value = [...selectedPelatihIds.value, pelatihId];
        }
    } else {
        selectedPelatihIds.value = selectedPelatihIds.value.filter((item) => item !== pelatihId);
    }
};

const isPelatihSelected = (id: number) => selectedPelatihIds.value.includes(Number(id));

const formInitialData = computed(() => ({
    nama_program: props.initialData?.nama_program || '',
    periode_mulai: props.initialData?.periode_mulai || '',
    periode_selesai: props.initialData?.periode_selesai || '',
    tahap: props.initialData?.tahap || '',
    keterangan: props.initialData?.keterangan || '',
    cabor_id: selectedCaborId.value,
    cabor_kategori_id: selectedKategoriId.value,
    id: props.initialData?.id || undefined,
}));

const formInputs = computed(() => [
    {
        name: 'cabor_id',
        label: 'Cabor',
        type: 'select' as const,
        options: caborOptions.value,
        placeholder: 'Pilih Cabor',
        required: true,
        modelValue: selectedCaborId.value,
        onUpdateModelValue: (val: any) => (selectedCaborId.value = val),
    },
    {
        name: 'cabor_kategori_id',
        label: 'Kategori',
        type: 'select' as const,
        options: caborKategoriOptions.value,
        placeholder: selectedCaborId.value ? 'Pilih Kategori' : 'Pilih cabor terlebih dahulu',
        required: true,
        disabled: !selectedCaborId.value,
        modelValue: selectedKategoriId.value,
        onUpdateModelValue: (val: any) => (selectedKategoriId.value = val),
    },
    {
        name: 'nama_program',
        label: 'Nama Program',
        type: 'text' as const,
        placeholder: 'Masukkan nama program',
        required: true,
    },
    {
        name: 'periode_mulai',
        label: 'Periode Mulai',
        type: 'date' as const,
        required: true,
    },
    {
        name: 'periode_selesai',
        label: 'Periode Selesai',
        type: 'date' as const,
        required: true,
    },
    {
        name: 'tahap',
        label: 'Tahap',
        type: 'select' as const,
        options: [
            { value: 'persiapan umum', label: 'Persiapan Umum' },
            { value: 'persiapan khusus', label: 'Persiapan Khusus' },
            { value: 'prapertandingan', label: 'Prapertandingan' },
            { value: 'pertandingan', label: 'Pertandingan' },
            { value: 'transisi', label: 'Transisi' },
        ],
        placeholder: 'Pilih tahap',
        required: false,
    },
    {
        name: 'keterangan',
        label: 'Keterangan',
        type: 'textarea' as const,
        placeholder: 'Masukkan keterangan (opsional)',
        required: false,
    },
]);

const handleSave = (form: any) => {
    if (!selectedPelatihIds.value.length) {
        toast({ title: 'Pilih minimal satu pelatih', variant: 'destructive' });
        return;
    }

    const dataToSave: Record<string, any> = {
        ...form,
        cabor_id: selectedCaborId.value,
        cabor_kategori_id: selectedKategoriId.value,
        mode_pelatih: modePelatih.value,
        pelatih_ids: selectedPelatihIds.value,
        pelatih_id: selectedPelatihIds.value[0],
        wajib_absen_atlet: wajibAbsenAtlet.value ? 1 : 0,
        absen_jam_mulai: absenJamMulai.value || null,
        absen_jam_selesai: absenJamSelesai.value || null,
    };
    if (props.mode === 'edit' && props.initialData?.id) {
        dataToSave.id = props.initialData.id;
    }
    save(dataToSave, {
        url: '/program-latihan',
        mode: props.mode,
        id: props.initialData?.id,
        successMessage: props.mode === 'create' ? 'Program latihan berhasil ditambahkan' : 'Program latihan berhasil diperbarui',
        errorMessage: props.mode === 'create' ? 'Gagal menyimpan program latihan' : 'Gagal memperbarui program latihan',
        redirectUrl: '/program-latihan',
    });
};

function handleFieldUpdate({ field, value }: { field: string; value: any }) {
    if (field === 'cabor_id') {
        selectedCaborId.value = value;
        selectedKategoriId.value = '';
        selectedPelatihIds.value = [];
        fetchCaborKategoriOptions(value);
    }
    if (field === 'cabor_kategori_id') {
        selectedKategoriId.value = value;
        selectedPelatihIds.value = [];
        fetchPelatihOptions(value, selectedCaborId.value);
    }
}
</script>

<template>
    <div class="space-y-4">
        <FormInput
            :form-inputs="formInputs"
            :initial-data="formInitialData"
            @save="handleSave"
            @field-updated="handleFieldUpdate"
            :disable-auto-reset="props.mode === 'create'"
        />

        <div class="rounded-lg border border-border bg-card p-4 mx-auto max-w-3xl space-y-4">
            <div>
                <Label class="font-medium">Pelatih Program</Label>
                <p class="text-sm text-muted-foreground mt-1 mb-3">
                    Pilih apakah program dipimpin oleh satu pelatih atau lebih dari satu pelatih.
                </p>
                <div class="flex flex-col gap-2 sm:flex-row sm:gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="mode_pelatih"
                            value="single"
                            :checked="modePelatih === 'single'"
                            @change="modePelatih = 'single'"
                            class="h-4 w-4"
                        />
                        <span>Satu pelatih</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="mode_pelatih"
                            value="multiple"
                            :checked="modePelatih === 'multiple'"
                            @change="modePelatih = 'multiple'"
                            class="h-4 w-4"
                        />
                        <span>Lebih dari satu pelatih</span>
                    </label>
                </div>
            </div>

            <div v-if="selectedKategoriId" class="space-y-2">
                <Label class="font-medium">
                    {{ modePelatih === 'single' ? 'Pilih Pelatih' : 'Pilih Pelatih (bisa lebih dari satu)' }}
                </Label>
                <div v-if="pelatihOptions.length === 0" class="text-sm text-muted-foreground">
                    Belum ada pelatih terdaftar pada kategori ini.
                </div>
                <div v-else class="grid gap-2 sm:grid-cols-2">
                    <div
                        v-for="option in pelatihOptions"
                        :key="option.value"
                        class="flex items-start gap-3 rounded-md border border-border p-3 cursor-pointer hover:bg-muted/40"
                        @click="togglePelatih(option.value, !isPelatihSelected(option.value))"
                    >
                        <Checkbox
                            :model-value="isPelatihSelected(option.value)"
                            @update:model-value="(val) => togglePelatih(option.value, val)"
                            @click.stop
                        />
                        <span class="text-sm">{{ option.label }}</span>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">Pilih cabor dan kategori terlebih dahulu untuk memilih pelatih.</p>
        </div>

        <div class="rounded-lg border border-border bg-card p-4 mx-auto max-w-3xl space-y-4">
            <div class="flex items-start gap-3">
                <Checkbox id="wajib_absen_atlet" v-model="wajibAbsenAtlet" />
                <div class="space-y-1">
                    <Label for="wajib_absen_atlet" class="cursor-pointer font-medium">
                        Atlet wajib absen
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        Jika dicentang, atlet pada program ini dapat dan diwajibkan melakukan absen harian dengan foto + GPS melalui aplikasi mobile.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="absen_jam_mulai">Jam Absen Mulai (WIB)</Label>
                    <input
                        id="absen_jam_mulai"
                        v-model="absenJamMulai"
                        type="time"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="absen_jam_selesai">Jam Absen Selesai (WIB)</Label>
                    <input
                        id="absen_jam_selesai"
                        v-model="absenJamSelesai"
                        type="time"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
            </div>
            <p class="text-xs text-muted-foreground">
                Opsional. Jika diisi, pelatih dan atlet hanya bisa absen/rekap dalam rentang jam tersebut.
            </p>
        </div>
    </div>
</template>
