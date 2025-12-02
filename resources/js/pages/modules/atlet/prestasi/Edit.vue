<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import FormPrestasi from './Form.vue';

const props = defineProps<{
    atletId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Prestasi', href: `/atlet/${props.atletId}/prestasi` },
    { title: 'Edit Prestasi', href: `/atlet/${props.atletId}/prestasi/${props.item.id}/edit` },
]);
</script>

<template>
    <PageEdit title="Prestasi" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/prestasi`">
        <FormPrestasi :atlet-id="props.atletId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
