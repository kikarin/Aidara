<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="429"
        title="Terlalu Banyak Permintaan"
        message="Anda mengirim permintaan terlalu cepat. Tunggu beberapa saat lalu coba lagi."
    />
</template>
