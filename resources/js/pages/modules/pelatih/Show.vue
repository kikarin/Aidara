<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import ShowDokumen from './dokumen/ShowDokumen.vue';
import ShowPrestasi from './prestasi/ShowPrestasi.vue';
import ShowSertifikat from './sertifikat/ShowSertifikat.vue';
import ShowCabor from './ShowCabor.vue';
import ShowKesehatan from './ShowKesehatan.vue';

const { toast } = useToast();

interface Sertifikat {
    id: number;
    pelatih_id: number;
    nama_sertifikat: string;
    penyelenggara?: string;
    tanggal_terbit?: string;
    file_url?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: { name: string } | null;
    updated_by_user?: { name: string } | null;
}

interface Prestasi {
    id: number;
    pelatih_id: number;
    nama_event: string;
    tingkat_id?: number;
    tingkat?: { nama: string } | null;
    tanggal?: string;
    peringkat?: string;
    keterangan?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: { name: string } | null;
    updated_by_user?: { name: string } | null;
}

interface Dokumen {
    id: number;
    pelatih_id: number;
    jenis_dokumen_id?: number;
    jenis_dokumen?: { nama: string } | null;
    nomor?: string;
    file_url?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: { name: string } | null;
    updated_by_user?: { name: string } | null;
}

interface Kesehatan {
    id: number;
    pelatih_id: number;
    tinggi_badan?: string;
    berat_badan?: string;
    penglihatan?: string;
    pendengaran?: string;
    riwayat_penyakit?: string;
    alergi?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: { name: string } | null;
    updated_by_user?: { name: string } | null;
}

interface CaborData {
    id: number;
    cabor_id: number;
    cabor_kategori_id: number;
    pelatih_id: number;
    jenis_pelatih_id: number;
    is_active: boolean;
    created_at: string;
    cabor: {
        id: number;
        nama: string;
        deskripsi: string;
    };
    cabor_kategori: {
        id: number;
        nama: string;
        jenis_kelamin: string;
        deskripsi: string;
    };
    jenis_pelatih: {
        id: number;
        nama: string;
    };
}

const props = defineProps<{
    item: {
        id: number;
        nik: string;
        nama: string;
        jenis_kelamin: string;
        tempat_lahir: string;
        tanggal_lahir: string;
        alamat: string;
        kecamatan_id: number | null;
        kelurahan_id: number | null;
        no_hp: string;
        email: string;
        pekerjaan_selain_melatih: string;
        is_active: number;
        foto: string;
        created_at: string;
        created_by_user: { id: number; name: string } | null;
        updated_at: string;
        updated_by_user: { id: number; name: string } | null;
        sertifikat?: Sertifikat[];
        prestasi?: Prestasi[];
        dokumen?: Dokumen[];
        kesehatan?: Kesehatan | null;
        cabor_kategori_pelatih?: CaborData[];
        kecamatan?: { nama: string } | null;
        kelurahan?: { nama: string } | null;
        tanggal_bergabung?: string;
        kategori_pesertas?: Array<{ id: number; nama: string }>;
    };
}>();

// Ambil tab dari query string
function getTabFromUrl(url: string, fallback = 'pelatih-data') {
    if (url.includes('tab=')) {
        return new URLSearchParams(url.split('?')[1]).get('tab') || fallback;
    }
    return fallback;
}

const page = usePage();
const initialTab = getTabFromUrl(page.url);
const activeTab = ref(initialTab);

watch(activeTab, (val) => {
    console.log('Tab berubah:', val);
    const url = `/pelatih/${props.item.id}?tab=${val}`;
    router.visit(url, { replace: true, preserveState: true, preserveScroll: true, only: [] });
});

watch(
    () => page.url,
    (newUrl) => {
        const tab = getTabFromUrl(newUrl);
        if (tab !== activeTab.value) {
            activeTab.value = tab;
        }
    },
);

const dynamicTitle = computed(() => {
    if (activeTab.value === 'pelatih-data') {
        return `Pelatih : ${props.item.nama}`;
    } else if (activeTab.value === 'cabor-data') {
        return `Cabor : ${props.item.nama}`;
    } else if (activeTab.value === 'sertifikat-data') {
        return `Sertifikat : ${props.item.nama}`;
    } else if (activeTab.value === 'prestasi-data') {
        return `Prestasi : ${props.item.nama}`;
    } else if (activeTab.value === 'kesehatan-data') {
        return `Kesehatan : ${props.item.nama}`;
    } else if (activeTab.value === 'dokumen-data') {
        return `Dokumen : ${props.item.nama}`;
    }
    return `Pelatih: ${props.item.nama}`;
});

const breadcrumbs = [
    { title: 'Pelatih', href: '/pelatih' },
    { title: 'Detail Pelatih', href: `/pelatih/${props.item.id}` },
];

const fields = computed(() => {
    return [
        { label: 'NIK', value: props.item?.nik || '-' },
        { label: 'Nama', value: props.item?.nama || '-' },
        {
            label: 'Jenis Kelamin',
            value: props.item?.jenis_kelamin === 'L' ? 'Laki-laki' : props.item?.jenis_kelamin === 'P' ? 'Perempuan' : '-',
        },
        { label: 'Tempat Lahir', value: props.item?.tempat_lahir || '-' },
        {
            label: 'Tanggal Lahir',
            value: props.item?.tanggal_lahir
                ? new Date(props.item.tanggal_lahir).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'numeric',
                      year: 'numeric',
                  })
                : '-',
        },
        {
            label: 'Tanggal Bergabung',
            value: props.item?.tanggal_bergabung
                ? new Date(props.item.tanggal_bergabung).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'numeric',
                      year: 'numeric',
                  })
                : '-',
        },
        { label: 'Lama Bergabung', value: props.item?.tanggal_bergabung ? getLamaBergabung(props.item.tanggal_bergabung) : '-' },
        { label: 'Alamat', value: props.item?.alamat || '-', className: 'sm:col-span-2' },
        { label: 'Kecamatan', value: props.item?.kecamatan?.nama || '-' },
        { label: 'Kelurahan', value: props.item?.kelurahan?.nama || '-' },
        { label: 'Pekerjaan Selain Melatih', value: props.item?.pekerjaan_selain_melatih || '-' },
        {
            label: 'Kategori Peserta',
            value:
                props.item?.kategori_pesertas && props.item.kategori_pesertas.length > 0
                    ? props.item.kategori_pesertas.map((k: { nama: string }) => k.nama).join(', ')
                    : '-',
        },
        { label: 'No HP', value: props.item?.no_hp || '-' },
        { label: 'Email', value: props.item?.email || '-' },
        {
            label: 'Status',
            value: props.item?.is_active ? 'Aktif' : 'Nonaktif',
            className: props.item?.is_active ? 'text-green-600' : 'text-red-600',
        },
        {
            label: 'Foto',
            value: props.item?.foto || '',
            type: 'image' as const,
            className: 'sm:col-span-2',
            imageConfig: {
                size: 'md' as const,
                labelText: 'Klik untuk melihat lebih besar',
            },
        },
    ];
});

const actionFields = computed(() => [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
]);

const kesehatanActionFields = computed(() => {
    const o = props.item.kesehatan;
    return [
        {
            label: 'Created At',
            value: o?.created_at ? new Date(o.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
        },
        {
            label: 'Created By',
            value: o?.created_by_user?.name || '-',
        },
        {
            label: 'Updated At',
            value: o?.updated_at ? new Date(o.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) : '-',
        },
        {
            label: 'Updated By',
            value: o?.updated_by_user?.name || '-',
        },
    ];
});

const tabsConfig = [
    {
        value: 'pelatih-data',
        label: 'Pelatih',
    },
    {
        value: 'cabor-data',
        label: 'Cabor',
    },
    {
        value: 'sertifikat-data',
        label: 'Sertifikat',
    },
    {
        value: 'prestasi-data',
        label: 'Prestasi',
    },
    {
        value: 'kesehatan-data',
        label: 'Kesehatan',
    },
    {
        value: 'dokumen-data',
        label: 'Dokumen',
    },
];

const handleEditPelatih = () => {
    router.visit(`/pelatih/${props.item.id}/edit`);
};

const handleDeletePelatih = () => {
    router.delete(`/pelatih/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Pelatih berhasil dihapus', variant: 'success' });
            router.visit('/pelatih');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus pelatih', variant: 'destructive' });
        },
    });
};

const handleEditKesehatan = () => {
    router.visit(`/pelatih/${props.item.id}/edit?tab=kesehatan-data`);
};

const handleDeleteKesehatan = () => {
    if (props.item.kesehatan) {
        router.delete(`/pelatih/${props.item.id}/kesehatan/${props.item.kesehatan.id}`, {
            onSuccess: () => {
                toast({ title: 'Data kesehatan pelatih berhasil dihapus', variant: 'success' });
                router.visit(`/pelatih/${props.item.id}?tab=kesehatan-data`, { replace: true, preserveState: true, preserveScroll: true, only: [] });
            },
            onError: () => {
                toast({ title: 'Gagal menghapus data kesehatan pelatih', variant: 'destructive' });
            },
        });
    }
};

const currentOnEditHandler = computed(() => {
    if (activeTab.value === 'pelatih-data') {
        return handleEditPelatih;
    } else if (activeTab.value === 'cabor-data') {
        return undefined;
    } else if (activeTab.value === 'sertifikat-data') {
        return undefined;
    } else if (activeTab.value === 'prestasi-data') {
        return undefined;
    } else if (activeTab.value === 'kesehatan-data') {
        return handleEditKesehatan;
    } else if (activeTab.value === 'dokumen-data') {
        return undefined;
    }
    return undefined;
});

const currentOnDeleteHandler = computed(() => {
    if (activeTab.value === 'pelatih-data') {
        return handleDeletePelatih;
    } else if (activeTab.value === 'cabor-data') {
        return undefined;
    } else if (activeTab.value === 'sertifikat-data') {
        return undefined;
    } else if (activeTab.value === 'prestasi-data') {
        return undefined;
    } else if (activeTab.value === 'kesehatan-data') {
        return props.item.kesehatan ? handleDeleteKesehatan : undefined;
    } else if (activeTab.value === 'dokumen-data') {
        return undefined;
    }
    return undefined;
});

const mappedPrestasi = computed(() =>
    (props.item.prestasi || []).map((p) => ({
        ...p,
        tingkat: p.tingkat || (p.tingkat_id ? { nama: '-' } : undefined),
    })),
);
const mappedDokumen = computed(() =>
    (props.item.dokumen || []).map((d) => ({
        ...d,
        jenis_dokumen: d.jenis_dokumen || (d.jenis_dokumen_id ? { nama: '-' } : undefined),
    })),
);

function getLamaBergabung(tanggalBergabung: string) {
    if (!tanggalBergabung) return '-';
    const start = new Date(tanggalBergabung);
    const now = new Date();
    let tahun = now.getFullYear() - start.getFullYear();
    let bulan = now.getMonth() - start.getMonth();
    if (bulan < 0) {
        tahun--;
        bulan += 12;
    }
    let result = '';
    if (tahun > 0) result += tahun + ' tahun ';
    if (bulan > 0) result += bulan + ' bulan';
    if (!result) result = 'Kurang dari 1 bulan';
    return result.trim();
}
</script>

<template>
    <PageShow
        :title="dynamicTitle"
        :breadcrumbs="breadcrumbs"
        :fields="activeTab === 'pelatih-data' ? fields : []"
        :actionFields="
            activeTab === 'cabor-data' || activeTab === 'sertifikat-data' || activeTab === 'prestasi-data' || activeTab === 'dokumen-data'
                ? []
                : activeTab === 'pelatih-data'
                  ? actionFields
                  : kesehatanActionFields
        "
        :back-url="'/pelatih'"
        :on-edit="currentOnEditHandler"
        :on-delete="currentOnDeleteHandler"
        :on-edit-label="activeTab === 'kesehatan-data' && !props.item.kesehatan ? 'Create' : 'Edit'"
        :on-edit-icon="activeTab === 'kesehatan-data' && !props.item.kesehatan ? Plus : Pencil"
    >
        <template #tabs>
            <AppTabs :tabs="tabsConfig" :default-value="'pelatih-data'" v-model="activeTab" />
        </template>
        <template #custom-action>
            <div v-if="activeTab === 'sertifikat-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/pelatih/${props.item.id}/sertifikat`)"
                >
                    Kelola Sertifikat
                </button>
            </div>
            <div v-if="activeTab === 'prestasi-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/pelatih/${props.item.id}/prestasi`)"
                >
                    Kelola Prestasi
                </button>
            </div>
            <div v-if="activeTab === 'dokumen-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/pelatih/${props.item.id}/dokumen`)"
                >
                    Kelola Dokumen
                </button>
            </div>
            <div v-if="activeTab === 'kesehatan-data'"></div>
        </template>
        <template #custom>
            <div v-if="activeTab === 'cabor-data'">
                <ShowCabor :cabor-list="props.item.cabor_kategori_pelatih || []" :pelatih-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'sertifikat-data'">
                <ShowSertifikat :sertifikat-list="props.item.sertifikat || []" :pelatih-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'prestasi-data'">
                <ShowPrestasi :prestasi-list="mappedPrestasi" :pelatih-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'kesehatan-data'">
                <ShowKesehatan :kesehatan="props.item.kesehatan || null" />
            </div>
            <div v-if="activeTab === 'dokumen-data'">
                <ShowDokumen :dokumen-list="mappedDokumen" :pelatih-id="props.item.id" />
            </div>
        </template>
    </PageShow>
</template>
