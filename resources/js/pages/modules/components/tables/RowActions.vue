<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import permissionService from '@/services/permissionService';
import { Activity, Bolt, CircleCheckBig, Edit, Eye, FileText, FolderKanban, MoreVertical, Trash2, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    id: string | number;
    baseUrl: string;
    moduleName?: string;
    actions?: { label: string; onClick: () => void; permission?: string }[];
    show?: boolean;
    edit?: boolean;
    delete?: boolean;
    onDelete?: () => void;
    permissions?: {
        detail?: boolean;
        edit?: boolean;
        delete?: boolean;
    };
    detailUrl?: string;
    editUrl?: string;
    deleteUrl?: string;
}>();

defineEmits<{
    detail: [id: string | number];
    edit: [id: string | number];
    delete: [id: string | number];
}>();

const permissionsLoaded = ref(false);

onMounted(() => {
    permissionService.refresh();
    permissionsLoaded.value = true;

    setTimeout(() => {
        if (!permissionsLoaded.value) {
            permissionsLoaded.value = true;
        }
    }, 2000);
});

// Permission checking
computed(() => {
    if (props.permissions?.detail !== undefined) return props.permissions.detail;
    if (!props.moduleName) return true;
    return permissionService.canRead(props.moduleName);
});

computed(() => {
    if (props.permissions?.edit !== undefined) return props.permissions.edit;
    if (!props.moduleName) return true;
    return permissionService.canUpdate(props.moduleName);
});

computed(() => {
    if (props.permissions?.delete !== undefined) return props.permissions.delete;
    if (!props.moduleName) return true;
    return permissionService.canDelete(props.moduleName);
});

const canCustomAction = (permission?: string) => {
    if (!permission) return true;

    try {
        const result = permissionService.hasPermission(permission);
        console.log(`Permission check for "${permission}":`, result);
        return result;
    } catch (error) {
        console.warn(`Error checking permission "${permission}":`, error);
        return false;
    }
};

// Get available actions
const getAvailableActions = computed(() => {
    const actions: Array<{
        label: string;
        action: () => void;
        icon: any;
    }> = [];

    if (props.actions && props.actions.length > 0) {
        props.actions.forEach((action) => {
            if (canCustomAction(action.permission)) {
                actions.push({
                    label: action.label,
                    action: action.onClick,
                    icon:
                        action.label === 'Detail'
                            ? Eye
                            : action.label === 'Edit'
                              ? Edit
                              : action.label === 'Edit Posisi'
                                ? Edit
                                : action.label === 'Lihat'
                                  ? FolderKanban
                                  : action.label === 'Set Permissions'
                                    ? Bolt
                                    : action.label === 'Delete'
                                      ? Trash2
                                      : action.label === 'Riwayat Pemeriksaan'
                                        ? Activity
                                        : action.label === 'Setujui'
                                          ? CircleCheckBig
                                          : action.label === 'Tolak'
                                            ? X
                                            : FileText,
                });
            }
        });
    }

    return actions;
});

const items = computed(() => {
    return getAvailableActions.value;
});

const hasActions = computed(() => {
    if (!permissionsLoaded.value) return false;

    if (!props.actions || props.actions.length === 0) return false;

    const availableActions = getAvailableActions.value;
    const result = availableActions.length > 0;

    if (props.actions.length > 0 && availableActions.length === 0) {
        console.log('RowActions Debug - Actions filtered out:', {
            moduleName: props.moduleName,
            totalActions: props.actions.length,
            availableActions: availableActions.length,
            actions: props.actions.map((a) => ({ label: a.label, permission: a.permission })),
            hasActions: result,
        });
    }

    return result;
});
</script>

<template>
    <div v-if="hasActions">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="icon">
                    <MoreVertical class="h-4 w-4" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent class="w-40">
                <DropdownMenuItem
                    v-for="item in items"
                    :key="item.label"
                    @click="item.action"
                    class="flex items-center gap-2"
                    :class="item.label === 'Delete' ? 'text-red-600' : ''"
                >
                    <component :is="item.icon" class="h-4 w-4" v-if="item.icon" />
                    {{ item.label }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
