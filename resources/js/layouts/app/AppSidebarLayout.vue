<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-vue-next';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { useLayoutBreadcrumbs } from '@/composables/useLayoutBreadcrumbs';
import { getPageTransitionKey } from '@/lib/navigation';
import ChatbotWidget from '@/components/chatbot/ChatbotWidget.vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/components/ui/toast/useToast';
import { computed, watch } from 'vue';

const page = usePage();
const { toast } = useToast();
const { breadcrumbs } = useLayoutBreadcrumbs();

const pageTransitionKey = computed(() => getPageTransitionKey(page.url));

const flash = computed(() => (page.props as Record<string, unknown>)?.flash as { error?: string; success?: string } | undefined);

watch(
    flash,
    (newFlash) => {
        if (newFlash?.error) {
            toast({ title: newFlash.error, variant: 'destructive' });
        }
        if (newFlash?.success) {
            toast({ title: newFlash.success, variant: 'success' });
        }
    },
    { immediate: true },
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="flex min-h-svh flex-col">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div v-if="flash?.error" class="mx-4 mt-4 shrink-0">
                <Alert variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertTitle>Kesalahan</AlertTitle>
                    <AlertDescription>{{ flash.error }}</AlertDescription>
                </Alert>
            </div>
            <div data-main-scroll class="relative min-h-0 flex-1 overflow-y-auto overflow-x-hidden">
                <Transition name="page" mode="out-in">
                    <div :key="pageTransitionKey" class="min-h-full">
                        <slot />
                    </div>
                </Transition>
            </div>
        </AppContent>
        <ChatbotWidget />
    </AppShell>
</template>
