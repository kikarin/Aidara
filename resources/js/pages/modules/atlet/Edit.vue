<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
// import AppTabs from '@/components/AppTabs.vue'; // Remove direct import
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Form from './Form.vue';
import FormAkun from './FormAkun.vue';
import FormKesehatan from './FormKesehatan.vue';
import FormOrangTua from './FormOrangTua.vue';
import FormParameterUmum from './FormParameterUmum.vue';

const props = defineProps<{ item: Record<string, any> }>();

const atletId = ref<number | null>(props.item.id || null);

// Ambil tab dari query string
function getTabFromUrl(url: string, fallback = 'atlet-data') {
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
    const url = `/atlet/${props.item.id}/edit?tab=${val}`;
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
    if (activeTab.value === 'atlet-data') {
        return `Atlet : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'orang-tua-data') {
        return `Orang Tua/Wali : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'sertifikat-data') {
        return `Sertifikat : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'prestasi-data') {
        return `Prestasi : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'dokumen-data') {
        return `Dokumen : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'kesehatan-data') {
        return `Kesehatan : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'akun-data') {
        return `Akun : ${props.item.nama || '-'}`;
    } else if (activeTab.value === 'parameter-umum-data') {
        return `Parameter Umum : ${props.item.nama || '-'}`;
    }
    return 'Edit Atlet';
});

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Data Atlet', href: `/atlet/${props.item.id}/edit` },
]);

// Configuration for tabs - FILTER berdasarkan registration_status
const tabsConfig = computed(() => {
    const allTabs = [
        {
            value: 'atlet-data',
            label: 'Atlet',
            component: Form,
            props: { mode: 'edit', initialData: props.item },
            allowedForPending: true, // Data diri bisa diakses
        },
        {
            value: 'parameter-umum-data',
            label: 'Parameter Umum',
            component: FormParameterUmum,
            props: { mode: 'edit', atletId: atletId.value },
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
        {
            value: 'orang-tua-data',
            label: 'Orang Tua/Wali',
            component: FormOrangTua,
            props: { atletId: atletId.value, mode: 'edit' },
            allowedForPending: false, // TIDAK bisa diakses untuk pending
        },
        {
            value: 'sertifikat-data',
            label: 'Sertifikat',
            isRedirectTab: true,
            onClick: () => router.visit(`/atlet/${props.item.id}/sertifikat`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'prestasi-data',
            label: 'Prestasi',
            isRedirectTab: true,
            onClick: () => router.visit(`/atlet/${props.item.id}/prestasi`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'dokumen-data',
            label: 'Dokumen',
            isRedirectTab: true,
            onClick: () => router.visit(`/atlet/${props.item.id}/dokumen`),
            allowedForPending: true, // Bisa diakses untuk pending
        },
        {
            value: 'kesehatan-data',
            label: 'Kesehatan',
            component: FormKesehatan,
            props: { atletId: atletId.value, mode: 'edit' },
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
        // Jika pending, hanya tampilkan tab yang allowedForPending = true
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
        back-url="/atlet"
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
                    @click="() => router.visit(`/atlet/${props.item.id}/sertifikat`)"
                >
                    Lihat Sertifikat
                </button>
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/prestasi`)"
                >
                    Lihat Prestasi
                </button>
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/dokumen`)"
                >
                    Lihat Dokumen
                </button>
                <button
                    class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm transition-colors"
                    @click="() => router.visit(`/atlet/${props.item.id}/kesehatan`)"
                >
                    Lihat Kesehatan
                </button>
            </div>
        </template>
    </PageEdit>
</template>
