<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useHandleFormSave } from '@/composables/useHandleFormSave';
import AppLayout from '@/layouts/AppLayout.vue';
import PermissionTree from '@/pages/modules/roles/PermissionTree.vue';
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

const { save } = useHandleFormSave();

const props = defineProps<{
    item: Record<string, any>;
    permissionGroups: Array<{ label: string; children: Array<{ id: number; label: string }> }>;
    selectedPermissions: number[];
}>();

console.log('SetPermissions.vue props.permissionGroups:', props.permissionGroups);
console.log('SetPermissions.vue props.selectedPermissions:', props.selectedPermissions);

const selectedPermissions = ref<number[]>([...props.selectedPermissions]);
const loading = ref(false);

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Roles', href: '/menu-permissions/roles' },
    { title: 'Set Permissions', href: '#' },
];

const savePermissions = () => {
    save(
        {
            id: props.item.id,
            permission_id: selectedPermissions.value,
        },
        {
            url: `/menu-permissions/roles/set-permissions/${props.item.id}`,
            mode: 'create',
            successMessage: 'Permission berhasil disimpan. Silakan kembali untuk melihat perubahan.',
            errorMessage: 'Gagal menyimpan permission.',
            onSuccess: () => {
                router.visit(`/menu-permissions/roles/set-permissions/${props.item.id}`, {
                    only: ['item', 'permissionGroups', 'selectedPermissions'],
                    preserveScroll: true,
                });
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Back Button -->
            <div>
                <Button variant="secondary" @click="router.visit('/menu-permissions/roles')">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <!-- Role Name -->
            <div class="bg-muted rounded border px-4 py-3 text-center font-medium">
                <span>Name: </span>
                <span class="text-foreground">{{ props.item.name }}</span>
            </div>

            <!-- Save Button -->
            <div class="flex justify-center gap-2">
                <Button :disabled="loading" @click="savePermissions">
                    <span v-if="loading">Saving...</span>
                    <span v-else>Save</span>
                </Button>
            </div>

            <!-- Permissions -->
            <PermissionTree :groups="props.permissionGroups" v-model="selectedPermissions" />
        </div>
    </AppLayout>
</template>
