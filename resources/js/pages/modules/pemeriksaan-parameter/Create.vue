<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const pemeriksaan = computed(() => page.props.pemeriksaan || {});
const pemeriksaanId = computed(() => pemeriksaan.value.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Pemeriksaan Parameter', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter` },
    { title: 'Tambah Pemeriksaan Parameter', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/create` },
];
</script>

<template>
    <PageCreate title="Tambah Pemeriksaan Parameter" :breadcrumbs="breadcrumbs" :back-url="`/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter`">
        <Form mode="create" />
    </PageCreate>
</template>
