<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="419"
        title="Sesi Kedaluwarsa"
        message="Halaman ini sudah kedaluwarsa. Silakan muat ulang halaman lalu coba lagi."
        action-label="Muat Ulang"
        reload
    />
</template>
