<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library, type IconName } from '@fortawesome/fontawesome-svg-core';
import { faCircleNotch, faClipboardList, faFileArrowUp, faReceipt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';

library.add(faCircleNotch, faClipboardList, faFileArrowUp, faReceipt);

const form = useForm({
    email: '',
    password: '',
    device_name: 'booking-web',
});

const submit = () => {
    form.post(route('e-booking.login.store'));
};

const points: Array<{ icon: IconName; text: string }> = [
    { icon: 'clipboard-list', text: 'Cek status pengajuan kapan saja.' },
    { icon: 'receipt', text: 'Lihat instruksi pembayaran setelah pengajuan disetujui.' },
    { icon: 'file-arrow-up', text: 'Unggah bukti transfer langsung dari halaman pesanan.' },
];
</script>

<template>
    <SeoHead title="Login Penyewa" description="Masuk ke Si Bola, layanan sewa venue UPT Dispora Kabupaten Bogor." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-start">
            <section aria-labelledby="login-title" class="sb-card p-6 sm:p-8">
                <p class="wp-eyebrow">Akun penyewa</p>
                <h1 id="login-title" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Masuk ke Si Bola</h1>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">Gunakan email dan kata sandi yang Anda daftarkan.</p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="sb-label" for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            required
                            autocomplete="username"
                            inputmode="email"
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
                            required
                            autocomplete="current-password"
                            class="sb-input"
                            :aria-invalid="form.errors.password ? 'true' : undefined"
                            :aria-describedby="form.errors.password ? 'password-error' : undefined"
                        />
                        <p v-if="form.errors.password" id="password-error" class="sb-error">{{ form.errors.password }}</p>
                    </div>
                    <button type="submit" class="wp-btn wp-btn-primary w-full px-5 py-3 text-sm" :disabled="form.processing">
                        <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                        Lanjut masuk
                    </button>
                </form>

                <p class="text-muted-foreground mt-6 text-center text-sm">
                    Belum punya akun?
                    <Link
                        :href="route('e-booking.register')"
                        class="rounded-sm font-semibold text-(--wp-accent-strong) underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                    >
                        Buat akun sekarang
                    </Link>
                </p>
            </section>

            <aside aria-labelledby="login-info-title" class="sb-card-muted p-6 sm:p-8 lg:sticky lg:top-24">
                <SiBolaMark class="size-10" />
                <h2 id="login-info-title" class="mt-5 text-lg font-semibold tracking-tight">Pantau pesanan dari satu tempat</h2>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                    Venue milik Pemkab Bogor, dikelola UPT Dispora. Setelah masuk, Anda bisa:
                </p>
                <ul class="mt-6 space-y-4">
                    <li v-for="point in points" :key="point.icon" class="flex items-start gap-3">
                        <span class="wp-icon size-8 shrink-0">
                            <FontAwesomeIcon :icon="['fas', point.icon]" class="size-3.5" aria-hidden="true" />
                        </span>
                        <span class="pt-1.5 text-sm leading-relaxed">{{ point.text }}</span>
                    </li>
                </ul>
            </aside>
        </div>
    </EBookingLayout>
</template>
