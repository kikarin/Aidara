<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
    wide?: boolean;
}>();
</script>

<template>
    <div class="welcome-page bg-background relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden p-6 md:p-10">
        <div class="welcome-hero absolute inset-0 opacity-40"></div>

        <Link
            :href="route('home')"
            class="text-muted-foreground hover:text-foreground relative z-10 inline-flex items-center gap-1.5 self-start text-sm font-medium transition-colors md:absolute md:top-6 md:left-8"
        >
            <ArrowLeft class="h-4 w-4" />
            Kembali ke Beranda
        </Link>

        <div class="relative z-10 w-full" :class="wide ? 'max-w-2xl' : 'max-w-sm'">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('home')" class="flex flex-col items-center gap-3 font-medium">
                        <div class="flex items-center gap-2 rounded-2xl bg-white/80 p-3 shadow-md dark:bg-gray-900/60">
                            <img src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-10 object-contain" />
                            <span class="bg-border/80 h-9 w-px"></span>
                            <img src="/Logo.png" alt="Logo Dispora Kabupaten Bogor" class="size-10 object-contain" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <p class="text-muted-foreground text-xs font-semibold tracking-widest uppercase">AIDARA — Dispora Kabupaten Bogor</p>
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-muted-foreground text-center text-sm">{{ description }}</p>
                    </div>
                </div>

                <!-- Form container with simple background -->
                <div class="bg-card border-border/60 rounded-xl border p-6 shadow-sm">
                    <slot />
                </div>

                <div class="space-y-2 text-center text-xs text-muted-foreground">
                    <p>Dengan menggunakan Aidara, Anda menyetujui kebijakan berikut:</p>
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                        <Link :href="route('legal.show', { slug: 'terms' })" class="text-primary hover:underline">
                            Syarat & Ketentuan
                        </Link>
                        <span class="text-muted-foreground/50">•</span>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="text-primary hover:underline">
                            Kebijakan Privasi
                        </Link>
                        <span class="text-muted-foreground/50">•</span>
                        <Link :href="route('legal.show', { slug: 'pdp' })" class="text-primary hover:underline">
                            PDP
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
