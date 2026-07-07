<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    active?: 'list' | 'detail';
}>();
</script>

<template>
    <header class="border-border/60 bg-background/80 sticky top-0 z-50 border-b backdrop-blur-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
            <Link :href="route('home')" class="flex items-center gap-3" aria-label="AIDARA — kembali ke beranda">
                <div class="flex items-center gap-2">
                    <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" :lazy="false" />
                    <span class="bg-border/80 h-8 w-px" aria-hidden="true"></span>
                    <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-9 object-contain" :lazy="false" />
                </div>
                <div class="hidden sm:block">
                    <p class="text-foreground text-sm font-bold">AIDARA</p>
                    <p class="text-muted-foreground text-xs">Dispora Kabupaten Bogor</p>
                </div>
            </Link>

            <nav class="hidden items-center gap-5 text-sm font-medium md:flex" aria-label="Navigasi utama">
                <Link :href="route('home')" class="text-muted-foreground hover:text-foreground transition-colors">Beranda</Link>
                <Link
                    :href="route('event.public.index')"
                    class="transition-colors"
                    :class="active ? 'text-foreground font-semibold' : 'text-muted-foreground hover:text-foreground'"
                    :aria-current="active ? 'page' : undefined"
                >
                    Event
                </Link>
                <Link
                    v-if="!$page.props.auth?.user"
                    :href="route('login')"
                    class="text-muted-foreground hover:text-foreground transition-colors"
                >
                    Login
                </Link>
                <Link v-else :href="route('dashboard')" class="text-muted-foreground hover:text-foreground transition-colors">
                    Dashboard
                </Link>
            </nav>

            <Link
                :href="route('home')"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 text-sm font-medium transition-colors md:hidden"
                aria-label="Kembali ke beranda"
            >
                Beranda
            </Link>
        </div>
    </header>
</template>
