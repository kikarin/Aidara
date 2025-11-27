<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';
import FormKesehatan from './FormKesehatan.vue';
import FormDokumen from './dokumen/Form.vue';
import FormPrestasi from './prestasi/Form.vue';
import FormSertifikat from './sertifikat/Form.vue';

import { computed, ref } from 'vue';

const activeTab = ref('pelatih-data');

// Computed property untuk judul dinamis
const dynamicTitle = computed(() => {
    if (activeTab.value === 'pelatih-data') {
        return 'Create Pelatih';
    } else if (activeTab.value === 'sertifikat-data') {
        return 'Create Pelatih Sertifikat';
    }
    return 'Create Pelatih';
});

// Computed property untuk breadcrumbs dinamis
const breadcrumbs = computed(() => [
    { title: 'Pelatih', href: '/pelatih' },
    { title: dynamicTitle.value, href: '/pelatih/create' },
]);

// Configuration for tabs
const tabsConfig = computed(() => [
    {
        value: 'pelatih-data',
        label: 'Pelatih',
        component: Form,
        props: { mode: 'create', initialData: {} },
    },
    {
        value: 'sertifikat-data',
        label: 'Sertifikat',
        component: FormSertifikat,
        props: { pelatihId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'prestasi-data',
        label: 'Prestasi',
        component: FormPrestasi,
        props: { pelatihId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'kesehatan-data',
        label: 'Kesehatan',
        component: FormKesehatan,
        props: { pelatihId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'dokumen-data',
        label: 'Dokumen',
        component: FormDokumen,
        props: { pelatihId: null, mode: 'create' },
        disabled: true,
    },
]);
</script>

<template>
    <PageCreate :title="dynamicTitle" :breadcrumbs="breadcrumbs" back-url="/pelatih" :tabs-config="tabsConfig" v-model:activeTabValue="activeTab" />
</template>
