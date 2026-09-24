<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    device_name: 'booking-admin',
});

const submit = () => form.post(route('e-booking.admin.login.store'));
</script>

<template>
    <SeoHead title="Login Admin UPT" />

    <AdminLayout>
        <div class="mx-auto max-w-md">
            <h1 class="text-foreground text-2xl font-bold">Masuk sebagai pengelola</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                Halaman ini khusus untuk mengelola pengajuan sewa fasilitas.
            </p>

            <form class="mt-8 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1.5 block text-sm font-medium" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium" for="password">Kata sandi</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                    />
                    <InputError :message="form.errors.password" />
                </div>
                <button
                    type="submit"
                    class="bg-[var(--brand-green,#2e7d32)] inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    Masuk
                </button>
            </form>

            <p class="text-muted-foreground mt-6 text-center text-xs">Demo: admin.upt@test.local / password123</p>
        </div>
    </AdminLayout>
</template>
