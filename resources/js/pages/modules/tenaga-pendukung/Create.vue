<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';
import FormKesehatan from './FormKesehatan.vue';
import FormDokumen from './dokumen/Form.vue';
import FormPrestasi from './prestasi/Form.vue';
import FormSertifikat from './sertifikat/Form.vue';

import { computed, ref } from 'vue';

const activeTab = ref('tenaga-pendukung-data');

// Computed property untuk judul dinamis
const dynamicTitle = computed(() => {
    if (activeTab.value === 'tenaga-pendukung-data') {
        return 'Create Tenaga Pendukung';
    } else if (activeTab.value === 'sertifikat-data') {
        return 'Create Sertifikat Tenaga Pendukung';
    }
    return 'Create Tenaga Pendukung';
});

// Computed property untuk breadcrumbs dinamis
const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: dynamicTitle.value, href: '/tenaga-pendukung/create' },
]);

// Configuration for tabs
const tabsConfig = computed(() => [
    {
        value: 'tenaga-pendukung-data',
        label: 'Tenaga Pendukung',
        component: Form,
        props: { mode: 'create', initialData: {} },
    },
    {
        value: 'sertifikat-data',
        label: 'Sertifikat',
        component: FormSertifikat,
        props: { tenagaPendukungId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'prestasi-data',
        label: 'Prestasi',
        component: FormPrestasi,
        props: { tenagaPendukungId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'kesehatan-data',
        label: 'Kesehatan',
        component: FormKesehatan,
        props: { tenagaPendukungId: null, mode: 'create' },
        disabled: true,
    },
    {
        value: 'dokumen-data',
        label: 'Dokumen',
        component: FormDokumen,
        props: { tenagaPendukungId: null, mode: 'create' },
        disabled: true,
    },
]);
</script>

<template>
    <PageCreate
        :title="dynamicTitle"
        :breadcrumbs="breadcrumbs"
        back-url="/tenaga-pendukung"
        :tabs-config="tabsConfig"
        v-model:activeTabValue="activeTab"
    />
</template>
