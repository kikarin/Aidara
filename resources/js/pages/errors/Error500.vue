<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="500"
        title="Kesalahan Server"
        message="Terjadi gangguan pada server. Tim kami sedang menangani masalah ini."
    />
</template>
