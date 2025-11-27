<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { computed } from 'vue';
import FormSertifikat from './Form.vue';

const props = defineProps<{ atletId: number }>();

const breadcrumbs = computed(() => [
    { title: 'Atlet', href: '/atlet' },
    { title: 'Sertifikat', href: `/atlet/${props.atletId}/sertifikat` },
    { title: 'Create Sertifikat', href: `/atlet/${props.atletId}/sertifikat/create` },
]);
</script>

<template>
    <PageCreate title="Create Sertifikat" :breadcrumbs="breadcrumbs" :back-url="`/atlet/${props.atletId}/sertifikat`">
        <FormSertifikat :atlet-id="props.atletId" mode="create" :initial-data="{}" />
    </PageCreate>
</template>
