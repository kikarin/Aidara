<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
    title: string;
    message: string;
    actionLabel?: string;
    actionHref?: string;
    reload?: boolean;
}>();

const statusLabel = computed(() => String(props.status));

const handleAction = () => {
    if (props.reload) {
        router.reload();
        return;
    }
};
</script>

<template>
    <Head :title="`${status} — ${title}`">
        <meta head-key="robots" content="noindex, nofollow" name="robots" />
    </Head>

    <div class="welcome-page bg-background text-foreground flex min-h-screen flex-col items-center justify-center px-6 py-12">
        <div class="w-full max-w-lg text-center">
            <div class="mb-6 flex justify-center">
                <div class="flex items-center gap-2 rounded-2xl bg-white/80 p-3 shadow-md dark:bg-gray-900/60">
                    <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-10 object-contain" :lazy="false" />
                    <span class="bg-border/80 h-9 w-px"></span>
                    <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-10 object-contain" :lazy="false" />
                </div>
            </div>

            <p class="text-[var(--brand-green,#2e7d32)] mb-2 text-sm font-semibold tracking-widest uppercase">AIDARA</p>
            <p class="text-muted-foreground mb-3 text-6xl font-black tabular-nums">{{ statusLabel }}</p>
            <h1 class="mb-3 text-2xl font-bold">{{ title }}</h1>
            <p class="text-muted-foreground mb-8 text-sm leading-relaxed">{{ message }}</p>

            <div class="flex flex-wrap items-center justify-center gap-3">
                <Button v-if="reload" @click="handleAction">{{ actionLabel ?? 'Muat Ulang' }}</Button>
                <Button v-else as-child>
                    <Link :href="actionHref ?? route('home')">{{ actionLabel ?? 'Kembali ke Beranda' }}</Link>
                </Button>
                <Button v-if="status === 403 || status === 401" as-child variant="outline">
                    <Link :href="route('login')">Masuk</Link>
                </Button>
            </div>
        </div>
    </div>
</template>
