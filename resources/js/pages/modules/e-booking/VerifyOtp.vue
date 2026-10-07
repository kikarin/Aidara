<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library, type IconName } from '@fortawesome/fontawesome-svg-core';
import { faArrowRightFromBracket, faCircleNotch, faEnvelope, faShieldHalved } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

library.add(faArrowRightFromBracket, faCircleNotch, faEnvelope, faShieldHalved);

defineProps<{
    email?: string;
}>();

const form = useForm({
    otp: '',
});

const resendForm = useForm({});
const cooldownSeconds = ref(0);
let cooldownInterval: ReturnType<typeof setInterval> | null = null;

const canResend = computed(() => cooldownSeconds.value === 0 && !resendForm.processing);

const startCooldown = () => {
    cooldownSeconds.value = 60;

    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }

    cooldownInterval = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value--;
        } else if (cooldownInterval) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
    }, 1000);
};

const submit = () => {
    form.post(route('e-booking.otp.verify'), {
        onError: () => {
            form.reset('otp');
        },
    });
};

const resendOtp = () => {
    if (!canResend.value) return;

    resendForm.post(route('e-booking.otp.resend'), {
        onSuccess: () => {
            form.reset('otp');
            startCooldown();
        },
        onError: () => {
            startCooldown();
        },
    });
};

onUnmounted(() => {
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});

const points: Array<{ icon: IconName; text: string }> = [
    { icon: 'shield-halved', text: 'Kode berlaku 10 menit dan hanya bisa dipakai satu kali.' },
    { icon: 'envelope', text: 'Cek folder spam bila kode tidak terlihat di kotak masuk.' },
];
</script>

<template>
    <SeoHead title="Verifikasi Email" description="Verifikasi email akun penyewa Si Bola dengan kode OTP." />

    <EBookingLayout active="auth">
        <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-start">
            <section aria-labelledby="verify-title" class="sb-card p-6 sm:p-8">
                <p class="wp-eyebrow">Verifikasi akun</p>
                <h1 id="verify-title" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Masukkan kode OTP</h1>
                <p class="text-muted-foreground mt-3 max-w-xl text-sm leading-relaxed">
                    Kami telah mengirim kode OTP 6 digit ke email Anda. Masukkan kode tersebut untuk mengaktifkan akun penyewa.
                </p>

                <div class="mt-6 flex items-center gap-3 rounded-2xl bg-(--wp-accent-soft) p-4 text-(--wp-accent-strong)">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-(--wp-accent) text-(--wp-accent-contrast)">
                        <FontAwesomeIcon :icon="['fas', 'envelope']" class="size-4" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-semibold tracking-wide uppercase opacity-70">Kode dikirim ke</span>
                        <span class="block truncate text-sm font-semibold">{{ email || 'email@example.com' }}</span>
                    </span>
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="sb-label" for="otp">Kode OTP</label>
                        <input
                            id="otp"
                            v-model="form.otp"
                            type="text"
                            name="otp"
                            required
                            autofocus
                            maxlength="6"
                            pattern="[0-9]{6}"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="000000"
                            class="sb-input text-center font-mono text-2xl tracking-[0.5em] tabular-nums"
                            :aria-invalid="form.errors.otp ? 'true' : undefined"
                            :aria-describedby="form.errors.otp ? 'otp-error' : 'otp-hint'"
                            @input="form.otp = form.otp.replace(/[^0-9]/g, '')"
                        />
                        <p v-if="form.errors.otp" id="otp-error" class="sb-error">{{ form.errors.otp }}</p>
                        <p v-else id="otp-hint" class="sb-hint">Kode OTP berlaku selama 10 menit.</p>
                    </div>

                    <button
                        type="submit"
                        class="wp-btn wp-btn-primary w-full justify-center px-5 py-3 text-sm"
                        :disabled="form.processing || form.otp.length !== 6"
                    >
                        <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                        Verifikasi email
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-muted-foreground mb-2 text-sm">Tidak menerima kode?</p>
                    <button
                        type="button"
                        class="wp-btn wp-btn-quiet w-full justify-center px-5 py-3 text-sm"
                        :disabled="!canResend"
                        @click="resendOtp"
                    >
                        <FontAwesomeIcon
                            v-if="resendForm.processing"
                            :icon="['fas', 'circle-notch']"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        <span v-if="cooldownSeconds > 0">Kirim ulang dalam {{ cooldownSeconds }} detik</span>
                        <span v-else>Kirim ulang kode OTP</span>
                    </button>
                </div>

                <div class="mt-6 text-center text-sm">
                    <Link
                        :href="route('e-booking.logout')"
                        method="post"
                        as="button"
                        type="button"
                        class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 rounded-sm font-medium underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                    >
                        <FontAwesomeIcon :icon="['fas', 'arrow-right-from-bracket']" class="size-3.5" aria-hidden="true" />
                        Keluar dan gunakan email lain
                    </Link>
                </div>
            </section>

            <aside aria-labelledby="verify-info-title" class="sb-card-muted p-6 sm:p-8 lg:sticky lg:top-24">
                <SiBolaMark class="size-10" />
                <h2 id="verify-info-title" class="mt-5 text-lg font-semibold tracking-tight">Kenapa perlu verifikasi?</h2>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
                    Email aktif memastikan undangan meeting dan surat resmi sampai ke Anda.
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
