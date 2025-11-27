<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, UserCheck, Users } from 'lucide-vue-next'; // ⬅ Tambah ChevronDown
import { computed } from 'vue';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => (page.props as any)?.auth?.user);

const allRoles = computed(() => {
    if (!user.value?.users_role) return [];
    return user.value.users_role.map((ur: any) => ur.role).filter(Boolean);
});

const hasMultipleRoles = computed(() => allRoles.value.length > 1);

const handleSwitchRole = (roleId: number) => {
    router.post(route('users.switch-role'), { role_id: roleId }, { preserveState: false });
};
</script>

<template>
    <header
        class="border-sidebar-border/70 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div v-if="user" class="ml-auto">
            <DropdownMenu v-if="hasMultipleRoles">
                <DropdownMenuTrigger>
                    <Badge class="hover:bg-primary/80 flex cursor-pointer items-center gap-2 px-3 py-2 text-sm" title="Click to switch role">
                        <UserCheck class="h-4 w-4" />
                        <span>{{ user.role.name }}</span>
                        <ChevronDown class="h-4 w-4 opacity-70" />
                        <!-- ⬅ Tambah panah -->
                    </Badge>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="animate-fade-in rounded-lg shadow-lg">
                    <DropdownMenuLabel class="flex items-center gap-2"> <Users class="h-4 w-4" /> Switch Role </DropdownMenuLabel>
                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        v-for="role in allRoles"
                        :key="role.id"
                        @click="handleSwitchRole(role.id)"
                        class="hover:bg-primary/10 flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 transition-colors"
                        :class="{
                            'bg-primary/10 text-primary font-semibold': role.id === user.role.id,
                        }"
                        :title="role.name"
                    >
                        <Check v-if="role.id === user.role.id" class="text-primary h-4 w-4" />
                        <span :class="{ 'pl-6': role.id !== user.role.id }">
                            {{ role.name }}
                        </span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Badge v-else class="flex items-center gap-2 px-3 py-2 text-sm">
                <UserCheck class="h-4 w-4" />
                {{ user.role.name }}
            </Badge>
        </div>
    </header>
</template>
