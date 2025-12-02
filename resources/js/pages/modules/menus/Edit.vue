<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listUsersMenu: Record<number, string>;
    get_Permission: Record<number, string>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Menus', href: '/menu-permissions/menus' },
    { title: 'Edit Menu', href: `/menu-permissions/menus/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Menu" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/menus">
        <Form mode="edit" :initial-data="item" :listUsersMenu="listUsersMenu" :get_Permission="get_Permission" />
    </PageEdit>
</template>
