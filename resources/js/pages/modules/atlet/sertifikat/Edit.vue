<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import { computed } from 'vue';
import FormSertifikat from './Form.vue';

const props = defineProps<{
    atletId: number;
    item: Record<string, any>;
}>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Sertifikat', href: `/atlet/${props.atletId}/sertifikat` },
    { title: 'Edit Sertifikat', href: `/atlet/${props.atletId}/sertifikat/${props.item.id}/edit` },
]);
</script>

<template>
    <PageEdit title="Sertifikat" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/sertifikat`">
        <FormSertifikat :atlet-id="props.atletId" mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
