<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const pemeriksaanId = computed(() => pemeriksaan.value.id);
const item = computed(() => page.props.item || {});

// Menentukan jenis peserta berdasarkan peserta_type
const jenisPeserta = computed(() => {
    const pesertaType = item.value?.peserta_type || '';
    if (pesertaType.includes('Atlet')) return 'atlet';
    if (pesertaType.includes('Pelatih')) return 'pelatih';
    if (pesertaType.includes('TenagaPendukung')) return 'tenaga-pendukung';
    return 'atlet';
});

// Label untuk jenis peserta
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
    { title: `Edit Pemeriksaan ${pesertaLabel.value}`, href: `/pemeriksaan/${pemeriksaanId.value}/peserta/${item.value.id}/edit` },
];
</script>

<template>
    <PageEdit :title="`${pesertaLabel}`" :breadcrumbs="breadcrumbs" :back-url="`/pemeriksaan/${pemeriksaanId}/peserta?jenis_peserta=${jenisPeserta}`">
        <Form mode="edit" :pemeriksaan="pemeriksaan" :initial-data="item" :jenis-peserta="jenisPeserta" />
    </PageEdit>
</template>
