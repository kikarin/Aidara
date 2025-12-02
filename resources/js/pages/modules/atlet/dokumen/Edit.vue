<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import FormDokumen from './Form.vue';

const props = defineProps<{
    atletId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Dokumen', href: `/atlet/${props.atletId}/dokumen` },
    { title: 'Edit Dokumen', href: `/atlet/${props.atletId}/dokumen/${props.item.id}/edit` },
]);
</script>

<template>
    <PageEdit title="Dokumen" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/dokumen`">
        <FormDokumen :atlet-id="props.atletId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
