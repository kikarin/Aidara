<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

const page = usePage();
const routeParams = computed(() => page.props.ziggy?.route_parameters || {});
const programId = computed(() => routeParams.value.program_id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));
const jenisTarget = computed(() => routeParams.value.jenis_target || (typeof window !== 'undefined' ? window.location.pathname.split('/')[4] : ''));

const props = defineProps<{ item: Record<string, any>; infoHeader?: any }>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}` },
    { title: 'Edit Target', href: `/program-latihan/${programId.value}/target-latihan/${jenisTarget.value}/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Target Latihan" :breadcrumbs="breadcrumbs" :back-url="`/program-latihan/${programId}/target-latihan/${jenisTarget}`">
        <Form mode="edit" :initial-data="item" :info-header="infoHeader" />
    </PageEdit>
</template>
