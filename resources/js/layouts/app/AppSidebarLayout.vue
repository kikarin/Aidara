<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { computed, watch } from 'vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const { toast } = useToast();

// Watch untuk flash messages
const flash = computed(() => (page.props as any)?.flash);
watch(flash, (newFlash) => {
    if (newFlash?.error) {
        toast({ title: newFlash.error, variant: 'destructive' });
    }
    if (newFlash?.success) {
        toast({ title: newFlash.success, variant: 'success' });
    }
}, { immediate: true });
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <!-- Flash Message Alert -->
            <div v-if="flash?.error" class="mx-4 mt-4">
                <Alert variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertTitle>Error</AlertTitle>
                    <AlertDescription>{{ flash.error }}</AlertDescription>
                </Alert>
            </div>
            <slot />
        </AppContent>
    </AppShell>
</template>
