<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
// import AppTabs from '@/components/AppTabs.vue'; // Remove direct import
import FormDokumen from './dokumen/Form.vue';
import Form from './Form.vue';
import FormKesehatan from './FormKesehatan.vue';
import FormOrangTua from './FormOrangTua.vue';
import FormPrestasi from './prestasi/Form.vue';
import FormSertifikat from './sertifikat/Form.vue';

import { computed, ref } from 'vue';

const activeTab = ref('atlet-data');

// Computed property untuk judul dinamis
const dynamicTitle = computed(() => {
    if (activeTab.value === 'atlet-data') {
        return 'Create Atlet';
    } else if (activeTab.value === 'orang-tua-data') {
        return 'Create Atlet Orang Tua/Wali';
    }
    return 'Create Atlet';
});

// Computed property untuk breadcrumbs dinamis
const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: dynamicTitle.value, href: '/atlet/create' },
]);

// Configuration for tabs
const tabsConfig = computed(() => [
    {
        value: 'atlet-data',
        label: 'Atlet',
        component: Form,
        props: { mode: 'create', initialData: {} },
    },
    {
        value: 'orang-tua-data',
        label: 'Orang Tua/Wali',
        component: FormOrangTua,
        props: { atletId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'sertifikat-data',
        label: 'Sertifikat',
        component: FormSertifikat,
        props: { atletId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'prestasi-data',
        label: 'Prestasi',
        component: FormPrestasi,
        props: { atletId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'dokumen-data',
        label: 'Dokumen',
        component: FormDokumen,
        props: { atletId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'kesehatan-data',
        label: 'Kesehatan',
        component: FormKesehatan,
        props: { atletId: null, mode: 'create' },
        disabled: true,
    },
]);
</script>

<template>
    <PageCreate :title="dynamicTitle" :breadcrumbs="breadcrumbs" back-url="/atlet" :tabs-config="tabsConfig" v-model:activeTabValue="activeTab" />
</template>
