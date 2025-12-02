<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const peserta = computed(() => page.props.peserta || {});
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
        title: 'Tambah Parameter Peserta',
        href: `/pemeriksaan/${pemeriksaan.value.id}/peserta/${peserta.value.id}/parameter/create?jenis_peserta=${jenisPeserta.value}`,
    },
];
</script>

<template>
    <PageCreate
        title="Tambah Parameter Peserta"
        :breadcrumbs="breadcrumbs"
        :back-url="`/pemeriksaan/${pemeriksaan.id}/peserta/${peserta.id}/parameter?jenis_peserta=${jenisPeserta}`"
    >
        <Form mode="create" :pemeriksaan="pemeriksaan" :peserta="peserta" :parameters="$page.props.parameters || []" />
    </PageCreate>
</template>
