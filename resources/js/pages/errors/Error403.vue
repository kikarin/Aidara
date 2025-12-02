<template>
    <div class="bg-background flex min-h-screen flex-col items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mb-6 flex justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="text-destructive h-20 w-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728"
                    />
                </svg>
            </div>
            <h1 class="mb-2 text-4xl font-bold">403 Forbidden</h1>
            <p class="text-muted-foreground mb-6">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
            <inertia-link
                href="/"
                class="bg-primary hover:bg-primary/90 inline-flex items-center gap-2 rounded-md px-4 py-2 text-white shadow transition"
            >
                Kembali ke Dashboard
            </inertia-link>
        </div>
    </div>
</template>

<script setup lang="ts">
// Tidak perlu logic khusus
</script>
