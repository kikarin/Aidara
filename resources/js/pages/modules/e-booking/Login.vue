<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    device_name: 'booking-web',
});

const submit = () => {
    form.post(route('e-booking.login.store'));
};
</script>

<template>
    <SeoHead title="Login Penyewa" description="Masuk ke E-Booking Fasilitas UPT Dispora." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-[32px] bg-[linear-gradient(135deg,#0f3d87_0%,#1479d1_58%,#44b6ff_100%)] p-7 text-white shadow-[0_20px_60px_rgba(20,121,209,0.25)] sm:p-8">
                <p class="text-sm font-semibold text-white/80">Masuk untuk lanjut booking</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Pantau pesanan, bayar, dan kirim bukti dari satu tempat.</h1>
                <div class="mt-6 space-y-3 text-sm text-white/90">
                    <p>• Cek status pengajuan kapan saja.</p>
                    <p>• Lihat instruksi pembayaran setelah disetujui.</p>
                    <p>• Unggah bukti transfer langsung dari halaman pesanan.</p>
                </div>
            </section>

            <section class="rounded-[32px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-2xl font-bold text-slate-900">Masuk</h2>
                <p class="mt-2 text-sm text-slate-600">Kalau belum punya akun, Anda tetap bisa buat akun baru dalam beberapa langkah.</p>

                <form class="mt-8 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="username"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[var(--brand-green,#2e7d32)] focus:outline-none"
                        />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-800" for="password">Kata sandi</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:ring-2 focus:ring-[var(--brand-green,#2e7d32)] focus:outline-none"
                        />
                        <InputError :message="form.errors.password" />
                    </div>
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-[var(--brand-green,#2e7d32)] px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Lanjut masuk
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Belum punya akun?
                    <Link :href="route('e-booking.register')" class="font-semibold text-[var(--brand-green,#2e7d32)] hover:underline">
                        Buat akun sekarang
                    </Link>
                </p>
            </section>
        </div>
    </EBookingLayout>
</template>
