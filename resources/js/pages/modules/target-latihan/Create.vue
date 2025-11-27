<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const routeParams = computed(() => page.props.ziggy?.route_parameters || {});
const programId = computed(() => routeParams.value.program_id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));
const jenisTarget = computed(() => routeParams.value.jenis_target || (typeof window !== 'undefined' ? window.location.pathname.split('/')[4] : ''));

defineProps<{ infoHeader?: any }>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}` },
    { title: 'Tambah Target', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/create` },
];
</script>

<template>
    <PageCreate title="Tambah Target Latihan" :breadcrumbs="breadcrumbs" :back-url="`/program-latihan/${programId}/target-latihan/${jenisTarget}`">
        <Form mode="create" :info-header="infoHeader" />
    </PageCreate>
</template>
