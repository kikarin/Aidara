<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import Form from './Form.vue';

const props = defineProps<{ tenagaPendukungId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Prestasi', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi` },
    { title: 'Create', href: `/tenaga-pendukung/${props.tenagaPendukungId}/prestasi/create` },
]);
</script>

<template>
    <PageCreate title="Buat Prestasi Tenaga Pendukung" :breadcrumbs="breadcrumbs" :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/prestasi`">
        <Form :tenaga-pendukung-id="props.tenagaPendukungId" mode="create" />
    </PageCreate>
</template>
