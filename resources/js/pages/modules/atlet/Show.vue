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
import ShowKesehatan from './ShowKesehatan.vue';
import ShowOrangTua from './ShowOrangTua.vue';
import ShowParameterKhusus from './ShowParameterKhusus.vue';
import ShowParameterUmum from './ShowParameterUmum.vue';
import ShowRekapLatihan from './ShowRekapLatihan.vue';

const { toast } = useToast();

interface Sertifikat {
    id: number;
    atlet_id: number;
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
    atlet_id: number;
    nama_event: string;
    tingkat_id?: number;
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
    atlet_id: number;
    jenis_dokumen_id?: number;
    nomor?: string;
    file_url?: string;
    created_at: string;
    updated_at: string;
    created_by_user?: { name: string } | null;
    updated_by_user?: { name: string } | null;
}

interface Kesehatan {
    id: number;
    atlet_id: number;
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

const props = defineProps<{
    item: {
        id: number;
        nik: string;
        nisn: string;
        nama: string;
        jenis_kelamin: string;
        tempat_lahir: string;
        tanggal_lahir: string;
        alamat: string;
        sekolah: string;
        kelas_sekolah: string;
        ukuran_baju: string;
        ukuran_celana: string;
        ukuran_sepatu: string;
        kecamatan_id: number | null;
        kelurahan_id: number | null;
        kategori_atlet_id: number | null;
        no_hp: string;
        email: string;
        is_active: number;
        foto: string;
        created_at: string;
        created_by_user: { id: number; name: string } | null;
        updated_at: string;
        updated_by_user: { id: number; name: string } | null;
        atlet_orang_tua?: {
            id: number;
            atlet_id: number;
            created_at: string;
            updated_at: string;
            created_by_user: { name: string } | null;
            updated_by_user: { name: string } | null;
        } | null;
        sertifikat?: Sertifikat[];
        prestasi?: Prestasi[];
        dokumen?: Dokumen[];
        kesehatan?: Kesehatan | null;
        kecamatan?: { nama: string } | null;
        kelurahan?: { nama: string } | null;
        kategori_atlet?: { id: number; nama: string } | null;
        kategori_atlets?: Array<{ id: number; nama: string }>;
        kategori_pesertas?: Array<{ id: number; nama: string }>;
    };
}>();

// Ambil tab dari query string
function getTabFromUrl(url: string, fallback = 'atlet-data') {
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
    const url = `/atlet/${props.item.id}?tab=${val}`;
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
    if (activeTab.value === 'atlet-data') {
        return `Atlet : ${props.item.nama}`;
    } else if (activeTab.value === 'orang-tua-data') {
        return `Orang Tua/Wali : ${props.item.nama}`;
    } else if (activeTab.value === 'sertifikat-data') {
        return `Sertifikat : ${props.item.nama}`;
    } else if (activeTab.value === 'prestasi-data') {
        return `Prestasi : ${props.item.nama}`;
    } else if (activeTab.value === 'dokumen-data') {
        return `Dokumen : ${props.item.nama}`;
    } else if (activeTab.value === 'kesehatan-data') {
        return `Kesehatan : ${props.item.nama}`;
    } else if (activeTab.value === 'rekap-latihan-data') {
        return `Rekap Latihan : ${props.item.nama}`;
    } else if (activeTab.value === 'parameter-umum-data') {
        return `Parameter Umum : ${props.item.nama}`;
    } else if (activeTab.value === 'parameter-khusus-data') {
        return `Parameter Khusus : ${props.item.nama}`;
    }
    return `Atlet: ${props.item.nama}`;
});

const breadcrumbs = [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Detail Atlet', href: `/atlet/${props.item.id}` },
];

const fields = computed(() => {
    return [
        { label: 'NIK', value: props.item?.nik || '-' },
        { label: 'NISN', value: props.item?.nisn || '-' },
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
            label: 'Agama',
            value: props.item?.agama || '-',
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
        { label: 'Sekolah', value: props.item?.sekolah || '-' },
        { label: 'Kelas Sekolah', value: props.item?.kelas_sekolah || '-' },
        { label: 'Ukuran Baju', value: props.item?.ukuran_baju || '-' },
        { label: 'Ukuran Celana', value: props.item?.ukuran_celana || '-' },
        { label: 'Ukuran Sepatu', value: props.item?.ukuran_sepatu || '-' },
        { label: 'Kecamatan', value: props.item?.kecamatan?.nama || '-' },
        { label: 'Kelurahan', value: props.item?.kelurahan?.nama || '-' },
        {
            label: 'Kategori Peserta',
            value:
                props.item?.kategori_pesertas && props.item.kategori_pesertas.length > 0
                    ? props.item.kategori_pesertas.map((k: { nama: string }) => k.nama).join(', ')
                    : props.item?.kategori_atlets && props.item.kategori_atlets.length > 0
                      ? props.item.kategori_atlets.map((k: { nama: string }) => k.nama).join(', ')
                      : props.item?.kategori_atlet?.nama || '-',
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

const orangTuaActionFields = computed(() => {
    const o = props.item.atlet_orang_tua;
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
        value: 'atlet-data',
        label: 'Atlet',
    },
    {
        value: 'orang-tua-data',
        label: 'Orang Tua/Wali',
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
        value: 'dokumen-data',
        label: 'Dokumen',
    },
    {
        value: 'kesehatan-data',
        label: 'Kesehatan',
    },
    {
        value: 'rekap-latihan-data',
        label: 'Rekap Latihan',
    },
    {
        value: 'parameter-umum-data',
        label: 'Parameter Umum',
    },
    {
        value: 'parameter-khusus-data',
        label: 'Parameter Khusus',
    },
];

const handleEditAtlet = () => {
    router.visit(`/atlet/${props.item.id}/edit`);
};

const handleDeleteAtlet = () => {
    router.delete(`/atlet/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Atlet berhasil dihapus', variant: 'success' });
            router.visit('/atlet');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus atlet', variant: 'destructive' });
        },
    });
};

const handleEditOrangTua = () => {
    router.visit(`/atlet/${props.item.id}/edit?tab=orang-tua-data`);
};

const handleDeleteOrangTua = () => {
    if (props.item.atlet_orang_tua) {
        router.delete(`/atlet/${props.item.id}/orang-tua/${props.item.atlet_orang_tua.id}`, {
            onSuccess: () => {
                toast({ title: 'Data orang tua/wali berhasil dihapus', variant: 'success' });
                router.visit(`/atlet/${props.item.id}?tab=orang-tua-data`, { replace: true, preserveState: true, preserveScroll: true, only: [] });
            },
            onError: () => {
                toast({ title: 'Gagal menghapus data orang tua/wali', variant: 'destructive' });
            },
        });
    }
};

const handleEditKesehatan = () => {
    router.visit(`/atlet/${props.item.id}/edit?tab=kesehatan-data`);
};

const handleDeleteKesehatan = () => {
    if (props.item.kesehatan) {
        router.delete(`/atlet/${props.item.id}/kesehatan/${props.item.kesehatan.id}`, {
            onSuccess: () => {
                toast({ title: 'Data kesehatan atlet berhasil dihapus', variant: 'success' });
                router.visit(`/atlet/${props.item.id}?tab=kesehatan-data`, { replace: true, preserveState: true, preserveScroll: true, only: [] });
            },
            onError: () => {
                toast({ title: 'Gagal menghapus data kesehatan atlet', variant: 'destructive' });
            },
        });
    }
};

const currentOnEditHandler = computed(() => {
    if (activeTab.value === 'atlet-data') {
        return handleEditAtlet;
    } else if (activeTab.value === 'orang-tua-data') {
        return handleEditOrangTua;
    } else if (activeTab.value === 'sertifikat-data') {
        return undefined;
    } else if (activeTab.value === 'prestasi-data') {
        return undefined;
    } else if (activeTab.value === 'dokumen-data') {
        return undefined;
    } else if (activeTab.value === 'kesehatan-data') {
        return handleEditKesehatan;
    } else if (activeTab.value === 'rekap-latihan-data' || activeTab.value === 'parameter-umum-data' || activeTab.value === 'parameter-khusus-data') {
        return undefined;
    }
    return undefined;
});

const currentOnDeleteHandler = computed(() => {
    if (activeTab.value === 'atlet-data') {
        return handleDeleteAtlet;
    } else if (activeTab.value === 'orang-tua-data') {
        return props.item.atlet_orang_tua ? handleDeleteOrangTua : undefined;
    } else if (activeTab.value === 'sertifikat-data') {
        return undefined;
    } else if (activeTab.value === 'prestasi-data') {
        return undefined;
    } else if (activeTab.value === 'dokumen-data') {
        return undefined;
    } else if (activeTab.value === 'kesehatan-data') {
        return props.item.kesehatan ? handleDeleteKesehatan : undefined;
    } else if (activeTab.value === 'rekap-latihan-data' || activeTab.value === 'parameter-umum-data' || activeTab.value === 'parameter-khusus-data') {
        return undefined;
    }
    return undefined;
});

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
        :fields="activeTab === 'atlet-data' ? fields : []"
        :actionFields="
            activeTab === 'sertifikat-data' ||
            activeTab === 'prestasi-data' ||
            activeTab === 'dokumen-data' ||
            activeTab === 'rekap-latihan-data' ||
            activeTab === 'parameter-umum-data' ||
            activeTab === 'parameter-khusus-data'
                ? []
                : activeTab === 'atlet-data'
                  ? actionFields
                  : activeTab === 'kesehatan-data'
                    ? kesehatanActionFields
                    : orangTuaActionFields
        "
        :back-url="'/atlet'"
        :on-edit="currentOnEditHandler"
        :on-delete="currentOnDeleteHandler"
        :on-edit-label="
            (activeTab === 'orang-tua-data' && !props.item.atlet_orang_tua) || (activeTab === 'kesehatan-data' && !props.item.kesehatan)
                ? 'Create'
                : activeTab === 'rekap-latihan-data' || activeTab === 'parameter-umum-data' || activeTab === 'parameter-khusus-data'
                  ? undefined
                  : 'Edit'
        "
        :on-edit-icon="
            (activeTab === 'orang-tua-data' && !props.item.atlet_orang_tua) || (activeTab === 'kesehatan-data' && !props.item.kesehatan)
                ? Plus
                : activeTab === 'rekap-latihan-data' || activeTab === 'parameter-umum-data' || activeTab === 'parameter-khusus-data'
                  ? undefined
                  : Pencil
        "
    >
        <template #tabs>
            <AppTabs :tabs="tabsConfig" :default-value="'atlet-data'" v-model="activeTab" />
        </template>
        <template #custom-action>
            <div v-if="activeTab === 'sertifikat-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/sertifikat`)"
                >
                    Kelola Sertifikat
                </button>
            </div>
            <div v-if="activeTab === 'prestasi-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/prestasi`)"
                >
                    Kelola Prestasi
                </button>
            </div>
            <div v-if="activeTab === 'dokumen-data'">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/dokumen`)"
                >
                    Kelola Dokumen
                </button>
            </div>
            <div v-if="activeTab === 'kesehatan-data'"></div>
        </template>
        <template #custom>
            <div v-if="activeTab === 'orang-tua-data'">
                <ShowOrangTua :orang-tua="props.item.atlet_orang_tua || null" />
            </div>
            <div v-if="activeTab === 'sertifikat-data'">
                <ShowSertifikat :sertifikat-list="props.item.sertifikat || []" :atlet-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'prestasi-data'">
                <ShowPrestasi :prestasi-list="props.item.prestasi || []" :atlet-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'dokumen-data'">
                <ShowDokumen :dokumen-list="props.item.dokumen || []" :atlet-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'kesehatan-data'">
                <ShowKesehatan :kesehatan="props.item.kesehatan || null" />
            </div>
            <div v-if="activeTab === 'rekap-latihan-data'">
                <ShowRekapLatihan :atlet-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'parameter-umum-data'">
                <ShowParameterUmum :atlet-id="props.item.id" />
            </div>
            <div v-if="activeTab === 'parameter-khusus-data'">
                <ShowParameterKhusus :atlet-id="props.item.id" />
            </div>
        </template>
    </PageShow>
</template>
