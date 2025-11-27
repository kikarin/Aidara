<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const peserta = computed(() => page.props.peserta || {});
const item = computed(() => page.props.item || {});
const jenisPeserta = computed(() => {
    return (
        page.props.jenis_peserta ||
        (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('jenis_peserta') || 'atlet' : 'atlet')
    );
});

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Peserta Pemeriksaan', href: `/pemeriksaan/${pemeriksaan.value.id}/peserta?jenis_peserta=${jenisPeserta.value}` },
    {
        title: 'Parameter Peserta',
        href: `/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter?jenis_peserta=${jenisPeserta.value}`,
    },
    {
        title: 'Edit Parameter Peserta',
        href: `/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter/${item.value.id}/edit?jenis_peserta=${jenisPeserta.value}`,
    },
];
</script>

<template>
    <PageEdit
        title="Parameter Peserta"
        :breadcrumbs="breadcrumbs"
        :back-url="`/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter?jenis_peserta=${jenisPeserta}`"
    >
        <Form mode="edit" :pemeriksaan="pemeriksaan" :peserta="peserta" :initial-data="item" :parameters="$page.props.parameters || []" />
    </PageEdit>
</template>
