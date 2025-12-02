<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const pemeriksaanId = computed(() => pemeriksaan.value.id);
const jenisPeserta = computed(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('jenis_peserta') || 'atlet';
    }
    return 'atlet';
});

const pesertaLabel = computed(() => {
    switch (jenisPeserta.value) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga-pendukung':
            return 'Tenaga Pendukung';
        default:
            return 'Peserta';
    }
});

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${pemeriksaanId.value}/peserta?jenis_peserta=${jenisPeserta.value}` },
    {
        title: `Tambah Pemeriksaan ${pesertaLabel.value}`,
        href: `/pemeriksaan/${pemeriksaanId.value}/peserta/create?jenis_peserta=${jenisPeserta.value}`,
    },
];
</script>

<template>
    <PageCreate
        :title="`Tambah ${pesertaLabel}`"
        :breadcrumbs="breadcrumbs"
        :back-url="`/pemeriksaan/${pemeriksaanId}/peserta?jenis_peserta=${jenisPeserta}`"
        :use-grid="true"
    >
        <Form mode="create" :pemeriksaan="pemeriksaan" :jenis-peserta="jenisPeserta" />
    </PageCreate>
</template>
