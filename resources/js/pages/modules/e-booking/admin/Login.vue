<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCircleNotch } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useForm } from '@inertiajs/vue3';

library.add(faCircleNotch);

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
        <div class="flex min-h-[60vh] items-center justify-center">
            <div class="sb-card w-full max-w-sm p-6 sm:p-8">
                <SiBolaMark class="size-10" />
                <h1 class="text-foreground mt-5 text-2xl font-bold tracking-tight">Masuk pengelola</h1>
                <p class="text-muted-foreground mt-1.5 text-sm">Halaman ini khusus untuk mengelola pengajuan sewa fasilitas.</p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="sb-label" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            autocomplete="username"
                            required
                            class="sb-input"
                            :aria-invalid="form.errors.email ? 'true' : undefined"
                            :aria-describedby="form.errors.email ? 'email-error' : undefined"
                        />
                        <p v-if="form.errors.email" id="email-error" class="sb-error">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="sb-label" for="password">Kata sandi</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            class="sb-input"
                            :aria-invalid="form.errors.password ? 'true' : undefined"
                            :aria-describedby="form.errors.password ? 'password-error' : undefined"
                        />
                        <p v-if="form.errors.password" id="password-error" class="sb-error">{{ form.errors.password }}</p>
                    </div>
                    <button type="submit" class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm" :disabled="form.processing">
                        <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                        Masuk
                    </button>
                </form>

                <p class="text-muted-foreground mt-6 text-center text-xs">Demo: admin.upt@test.local / password123</p>
            </div>
        </div>
    </AdminLayout>
</template>
