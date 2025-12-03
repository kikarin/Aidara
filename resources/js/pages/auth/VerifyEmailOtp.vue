<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Mail } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    email?: string;
}>();

const form = useForm({
    otp: '',
});

const resendForm = useForm({});
const cooldownSeconds = ref(0);
const cooldownInterval = ref<number | null>(null);

const canResend = computed(() => cooldownSeconds.value === 0 && !resendForm.processing);

const startCooldown = () => {
    cooldownSeconds.value = 60; // 60 detik cooldown
    cooldownInterval.value = setInterval(() => {
        if (cooldownSeconds.value > 0) {
            cooldownSeconds.value--;
        } else {
            if (cooldownInterval.value) {
                clearInterval(cooldownInterval.value);
                cooldownInterval.value = null;
            }
        }
    }, 1000);
};

const submit = () => {
    form.post(route('email.otp.verify'), {
        onError: () => {
            form.reset('otp');
        },
    });
};

const resendOtp = () => {
    if (!canResend.value) return;
    
    resendForm.post(route('email.otp.resend'), {
        onSuccess: () => {
            // Reset form setelah resend berhasil
            form.reset('otp');
            // Start cooldown timer setelah pertama kali klik
            startCooldown();
        },
        onError: () => {
            // Jika ada error cooldown dari server, start cooldown juga
            startCooldown();
        },
    });
};

onUnmounted(() => {
    if (cooldownInterval.value) {
        clearInterval(cooldownInterval.value);
    }
});
</script>

<template>
    <AuthBase title="Verifikasi Email" description="Masukkan kode OTP yang telah dikirim ke email Anda">
        <Head title="Verifikasi Email" />

        <div class="flex flex-col gap-6">
            <div class="rounded-lg border bg-card p-6 text-card-foreground shadow-sm">
                <div class="mb-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                        <Mail class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <p class="text-sm font-medium">Email Terkirim</p>
                        <p class="text-xs text-muted-foreground">{{ email || 'email@example.com' }}</p>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground">
                    Kami telah mengirimkan kode OTP 6 digit ke email Anda. Silakan cek inbox atau folder spam.
                </p>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-2">
                    <Label for="otp">Kode OTP</Label>
                    <Input
                        id="otp"
                        type="text"
                        required
                        autofocus
                        maxlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        placeholder="000000"
                        v-model="form.otp"
                        class="text-center text-2xl font-mono tracking-widest"
                        :class="{ 'border-red-500': form.errors.otp }"
                        @input="form.otp = form.otp.replace(/[^0-9]/g, '')"
                    />
                    <InputError :message="form.errors.otp" />
                    <p class="text-xs text-muted-foreground">
                        Kode OTP berlaku selama 10 menit
                    </p>
                </div>

                <Button type="submit" class="w-full" :disabled="form.processing || form.otp.length !== 6">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Verifikasi Email
                </Button>
            </form>

            <div class="text-center">
                <p class="mb-2 text-sm text-muted-foreground">
                    Tidak menerima kode?
                </p>
                <Button
                    type="button"
                    variant="outline"
                    class="w-full"
                    :disabled="!canResend"
                    @click="resendOtp"
                >
                    <LoaderCircle v-if="resendForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-if="cooldownSeconds > 0">
                        Kirim Ulang dalam {{ cooldownSeconds }} detik
                    </span>
                    <span v-else>
                        Kirim Ulang Kode OTP
                    </span>
                </Button>
                <p v-if="cooldownSeconds > 0" class="mt-2 text-xs text-muted-foreground">
                    Tunggu {{ cooldownSeconds }} detik sebelum meminta kode baru
                </p>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                <TextLink :href="route('logout')" method="post" class="underline underline-offset-4">
                    Keluar dan gunakan email lain
                </TextLink>
            </div>
        </div>
    </AuthBase>
</template>

