<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{ atletId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Dokumen', href: `/atlet/${props.atletId}/dokumen` },
    { title: 'Create Dokumen', href: `/atlet/${props.atletId}/dokumen/create` },
]);
</script>

<template>
    <PageCreate title="Create Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/dokumen`">
        <FormDokumen :atlet-id="props.atletId" mode="create" :initial-data="{}" />
    </PageCreate>
</template>
