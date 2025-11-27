<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Form from './Form.vue';

interface Pemeriksaan {
    id: number;
    nama_pemeriksaan: string;
}

const page = usePage();
const pemeriksaan = computed(() => (page.props.pemeriksaan as Pemeriksaan) || ({} as Pemeriksaan));
const pemeriksaanId = computed(() => pemeriksaan.value?.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[2] : ''));

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: pemeriksaan.value?.nama_pemeriksaan || 'Parameter Pemeriksaan', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter` },
    { title: 'Edit Parameter Pemeriksaan', href: `/pemeriksaan/${pemeriksaanId.value}/pemeriksaan-parameter/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Parameter Pemeriksaan" :breadcrumbs="breadcrumbs" :back-url="`/pemeriksaan/${pemeriksaanId}/pemeriksaan-parameter`">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
