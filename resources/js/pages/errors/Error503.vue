<script setup lang="ts">
import ErrorScreen from '@/components/errors/ErrorScreen.vue';
</script>

<template>
    <ErrorScreen
        :status="503"
        title="Sedang Pemeliharaan"
        message="AIDARA sedang dalam pemeliharaan. Silakan kembali beberapa saat lagi."
    />
</template>
