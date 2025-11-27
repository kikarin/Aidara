<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{ tenagaPendukungId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Tenaga Pendukung', href: '/tenaga-pendukung' },
    { title: 'Dokumen', href: `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen` },
    { title: 'Create Dokumen', href: `/tenaga-pendukung/${props.tenagaPendukungId}/dokumen/create` },
]);
</script>

<template>
    <PageCreate title="Create Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/tenaga-pendukung/${props.tenagaPendukungId}/dokumen`">
        <FormDokumen :tenaga-pendukung-id="props.tenagaPendukungId" mode="create" :initial-data="{}" />
    </PageCreate>
</template>
