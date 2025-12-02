<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { CardContent } from '@/components/ui/card';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
</script>

<template>
    <AuthBase title="Registrasi Berhasil" description="Pengajuan registrasi Anda telah dikirim">
        <Head title="Registrasi Berhasil" />

        <CardContent class="flex flex-col items-center justify-center space-y-4 py-12 text-center">
            <CheckCircle2 class="h-16 w-16 text-green-500" />
            <h2 class="text-2xl font-bold">Pengajuan Registrasi Berhasil Dikirim</h2>
            <p class="text-muted-foreground max-w-md">
                Terima kasih telah mendaftar. Pengajuan registrasi Anda sedang menunggu persetujuan dari admin. Anda akan menerima notifikasi melalui
                email setelah pengajuan Anda disetujui.
            </p>
            <div class="pt-4">
                <Button @click="() => router.visit(route('login'))"> Kembali ke Halaman Login </Button>
            </div>
        </CardContent>
    </AuthBase>
</template>
