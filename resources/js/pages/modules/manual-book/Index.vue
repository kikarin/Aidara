<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Download, ExternalLink } from 'lucide-vue-next';

defineProps<{
    pdfUrl: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dasbor',
        href: '/dashboard',
    },
    {
        title: 'Manual Book',
        href: '/manual-book',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Manual Book" />

        <div class="flex min-h-[calc(100svh-4rem)] flex-col p-4 lg:p-6">
            <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-0.5">
                    <h2 class="text-xl font-semibold tracking-tight">Manual Book</h2>
                    <p class="text-muted-foreground text-sm">
                        Panduan penggunaan aplikasi AIDARA untuk seluruh role pengguna.
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <a :href="pdfUrl" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="size-4" />
                            Buka di tab baru
                        </a>
                    </Button>
                    <Button as-child>
                        <a :href="pdfUrl" download="MANUAL BOOK.pdf">
                            <Download class="size-4" />
                            Unduh PDF
                        </a>
                    </Button>
                </div>
            </div>

            <div class="bg-muted/30 min-h-0 flex-1 overflow-hidden rounded-lg border">
                <iframe :src="pdfUrl" title="Manual Book AIDARA" class="h-full min-h-[70vh] w-full" />
            </div>
        </div>
    </AppLayout>
</template>
