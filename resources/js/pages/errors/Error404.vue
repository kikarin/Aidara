<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="404"
        title="Halaman Tidak Ditemukan"
        message="Halaman yang Anda cari tidak ada atau sudah dipindahkan."
    />
</template>
