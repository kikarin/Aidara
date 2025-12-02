<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Form from './Form.vue';
import FormAkun from './FormAkun.vue';
import FormKesehatan from './FormKesehatan.vue';

interface TenagaPendukungItem {
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
    is_active: number;
    foto: string;
    created_at: string;
    created_by_user: { id: number; name: string } | null;
    updated_at: string;
    updated_by_user: { id: number; name: string } | null;
}

const props = defineProps<{
    item: TenagaPendukungItem;
}>();

const tenagaPendukungId = ref<number | null>(props.item.id || null);

// Ambil tab dari query string
function getTabFromUrl(url: string, fallback = 'tenaga-pendukung-data') {
    if (url.includes('tab=')) {
        return new URLSearchParams(url.split('?')[1]).get('tab') || fallback;
    }
    return fallback;
}

const page = usePage();

// Ambil user registration_status dari props
const user = computed(() => (page.props as any)?.auth?.user);
const registrationStatus = computed(() => user.value?.registration_status);
const isPendingRegistration = computed(() => registrationStatus.value === 'pending');
const isRejected = computed(() => registrationStatus.value === 'rejected');
const rejectionReason = computed(() => user.value?.registration_rejected_reason || '');

const initialActiveTab = getTabFromUrl(page.url);
const activeTab = ref(initialActiveTab);

watch(activeTab, (val) => {
    const url = `/tenaga-pendukung/${props.item.id}/edit?tab=${val}`;
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

// Computed property untuk judul dinamis
const dynamicTitle = computed(() => {
    if (activeTab.value === 'tenaga-pendukung-data') {
        return `Tenaga Pendukung : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'sertifikat-data') {
        return `Sertifikat : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'prestasi-data') {
        return `Prestasi : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'kesehatan-data') {
        return `Kesehatan : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'dokumen-data') {
        return `Dokumen : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'akun-data') {
        return `Akun : ${props.item.nama || '-'}`;
    }
    return 'Edit Tenaga Pendukung';
});

const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Data Tenaga Pendukung', href: `/tenaga-pendukung/${props.item.id}/edit` },
]);

// Configuration for tabs - FILTER berdasarkan registration_status
const tabsConfig = computed(() => {
    const allTabs = [
        {
            value: 'tenaga-pendukung-data',
            label: 'Tenaga Pendukung',
            component: Form,
            props: { mode: 'edit', initialData: props.item },
            allowedForPending: true, // Data diri bisa diakses
        },
        {
            value: 'sertifikat-data',
            label: 'Sertifikat',
            isRedirectTab: true,
            onClick: () => router.visit(`/tenaga-pendukung/${props.item.id}/sertifikat`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'prestasi-data',
            label: 'Prestasi',
            isRedirectTab: true,
            onClick: () => router.visit(`/tenaga-pendukung/${props.item.id}/prestasi`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'dokumen-data',
            label: 'Dokumen',
            isRedirectTab: true,
            onClick: () => router.visit(`/tenaga-pendukung/${props.item.id}/dokumen`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'kesehatan-data',
            label: 'Kesehatan',
            component: FormKesehatan,
            props: { tenagaPendukungId: tenagaPendukungId.value, mode: 'edit' },
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
        {
            value: 'akun-data',
            label: 'Akun',
            component: FormAkun,
            props: { mode: 'edit', initialData: props.item },
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
    ];

    // Filter tab berdasarkan registration_status
    if (isPendingRegistration.value) {
        return allTabs
            .filter(tab => tab.allowedForPending === true)
            .map(tab => ({
                ...tab,
                disabled: false,
            }));
    }

    // Jika sudah approved, semua tab bisa diakses
    return allTabs.map(tab => ({
        ...tab,
        disabled: false,
    }));
});
</script>

<template>
    <PageEdit
        :title="dynamicTitle"
        :breadcrumbs="breadcrumbs"
        back-url="/tenaga-pendukung"
        :tabs-config="tabsConfig"
        v-model:activeTabValue="activeTab"
        :show-edit-prefix="false"
    >
        <template #default>
            <!-- Catatan Penolakan -->
            <div v-if="isRejected && rejectionReason" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-red-800">Catatan Penolakan dari Administrator</h3>
                        <p class="mt-1 text-sm text-red-700">{{ rejectionReason }}</p>
                        <p class="mt-2 text-xs text-red-600">Silakan perbaiki data Anda sesuai catatan di atas, kemudian simpan perubahan untuk mengajukan ulang.</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/tenaga-pendukung/${props.item.id}/sertifikat`)"
                >
                    Lihat Sertifikat
                </button>
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/tenaga-pendukung/${props.item.id}/prestasi`)"
                >
                    Lihat Prestasi
                </button>
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/tenaga-pendukung/${props.item.id}/dokumen`)"
                >
                    Lihat Dokumen
                </button>
            </div>
        </template>
    </PageEdit>
</template>
