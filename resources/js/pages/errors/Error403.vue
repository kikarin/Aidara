<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="403"
        title="Akses Ditolak"
        message="Anda tidak memiliki izin untuk mengakses halaman ini."
        action-label="Kembali ke Beranda"
    />
</template>
