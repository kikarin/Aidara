<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    get_Roles: Record<number, string>;
    selected_roles?: number[];
    peserta_info?: Record<string, any> | null;
}>();

const breadcrumbs = [
    { title: 'Users', href: '/users' },
    { title: 'Edit User', href: `/users/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="User" :breadcrumbs="breadcrumbs" back-url="/users">
        <Form
            mode="edit"
            :initial-data="item"
            :roles="get_Roles"
            :selected-roles="selected_roles || []"
            :peserta-info="peserta_info"
        />
    </PageEdit>
</template>
