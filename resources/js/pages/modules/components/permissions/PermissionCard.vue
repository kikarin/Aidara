<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useToast } from '@/components/ui/toast/useToast';
import { router } from '@inertiajs/vue3';
import { Lock, MoreVertical } from 'lucide-vue-next';
import { ref } from 'vue';
import ConfirmDialog from '../ConfirmDialog.vue';

const { toast } = useToast();

const props = defineProps<{
    category: {
        id: number;
        name: string;
        permissions: { id: number; name: string }[];
    };
}>();

const confirmDeleteCategory = ref(false);
const permissionToDelete = ref<{ id: number; name: string } | null>(null);

const goTo = (path: string) => router.visit(path);

const handleDeleteCategory = () => {
    confirmDeleteCategory.value = true;
};

const confirmCategoryDelete = () => {
    router.delete(`/menu-permissions/permissions/${props.category.id}`, {
        onSuccess: () => toast({ title: 'Kategori Permission berhasil dihapus', variant: 'success' }),
        onError: () => toast({ title: 'Gagal menghapus kategori Permission', variant: 'destructive' }),
    });
    confirmDeleteCategory.value = false;
};

const handleDeletePermission = (permission: { id: number; name: string }) => {
    permissionToDelete.value = permission;
};

const confirmPermissionDelete = () => {
    if (!permissionToDelete.value) return;
    router.delete(`/menu-permissions/permissions/delete-permission/${permissionToDelete.value.id}`, {
        onSuccess: () => toast({ title: 'Permission berhasil dihapus', variant: 'success' }),
        onError: () => toast({ title: 'Gagal menghapus permission', variant: 'destructive' }),
    });
    permissionToDelete.value = null;
};
</script>

<template>
    <div class="bg-card overflow-hidden rounded-xl border shadow-sm">
        <!-- Category Header -->
        <div class="flex items-center justify-between border-b px-4 py-3">
            <h3 class="text-sm font-semibold tracking-tight">
                {{ category.name }}
            </h3>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="h-6 w-6">
                        <MoreVertical class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem @click="goTo(`/menu-permissions/permissions/create-permission?category_id=${category.id}`)">
                        Add Permission
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="goTo(`/menu-permissions/permissions/category/${category.id}/edit`)"> Edit Category </DropdownMenuItem>
                    <DropdownMenuItem @click="goTo(`/menu-permissions/permissions/category/${category.id}`)"> View Detail </DropdownMenuItem>
                    <DropdownMenuItem class="text-red-500" @click="handleDeleteCategory"> Delete Category </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <!-- Permissions List -->
        <ul class="divide-y">
            <li v-for="perm in category.permissions" :key="perm.id" class="hover:bg-muted/50 flex items-center justify-between px-4 py-2">
                <div class="text-foreground flex items-center gap-2 text-sm">
                    <Lock class="text-muted-foreground h-4 w-4" />
                    <span>{{ perm.name }}</span>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="h-6 w-6">
                            <MoreVertical class="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-40">
                        <DropdownMenuItem @click="goTo(`/menu-permissions/permissions/${perm.id}/edit-permission`)"> Edit </DropdownMenuItem>
                        <DropdownMenuItem @click="goTo(`/menu-permissions/permissions/${perm.id}/detail`)"> View Detail </DropdownMenuItem>
                        <DropdownMenuItem class="text-red-500" @click="handleDeletePermission(perm)"> Delete </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </li>
        </ul>
    </div>

    <!-- Confirm Delete Category -->
    <ConfirmDialog
        :show="confirmDeleteCategory"
        title="Hapus Kategori"
        :description="`Yakin ingin menghapus kategori '${category.name}'?`"
        @confirm="confirmCategoryDelete"
        @cancel="confirmDeleteCategory = false"
    />

    <!-- Confirm Delete Permission -->
    <ConfirmDialog
        :show="permissionToDelete !== null"
        title="Hapus Permission"
        :description="`Yakin ingin menghapus permission '${permissionToDelete?.name}'?`"
        @confirm="confirmPermissionDelete"
        @cancel="permissionToDelete = null"
    />
</template>
