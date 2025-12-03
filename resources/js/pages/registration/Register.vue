<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Recaptcha from '@/components/Recaptcha.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
<<<<<<< HEAD
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock } from 'lucide-vue-next';
=======
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
import { computed, ref } from 'vue';

const props = defineProps<{
    recaptchaSiteKey?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    password_confirmation: '',
    recaptcha_token: '',
<<<<<<< HEAD
});

=======
    otp: '',
});

const page = usePage();
const showOtpField = ref(false);
const emailSent = ref(false);
const resendOtpForm = useForm({});

// Cek flash message saat component mount
const flashMessage = page.props.flash as any;
if (flashMessage?.otp_sent) {
    showOtpField.value = true;
    emailSent.value = true;
}

>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const recaptchaRef = ref<InstanceType<typeof Recaptcha> | null>(null);
const recaptchaVerified = ref(false);

// Password strength validation
const passwordStrength = computed(() => {
    const password = form.password;
    if (!password) return { score: 0, label: '', checks: [] };

    const checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /\d/.test(password),
    };

    const score = Object.values(checks).filter(Boolean).length;
    let label = '';
    if (score === 0) label = '';
    else if (score === 1) label = 'Sangat Lemah';
    else if (score === 2) label = 'Lemah';
    else if (score === 3) label = 'Sedang';
    else if (score === 4) label = 'Kuat';

    return { score, label, checks };
});

const passwordStrengthColor = computed(() => {
    const score = passwordStrength.value.score;
    if (score === 0) return '';
    if (score <= 1) return 'text-red-500';
    if (score === 2) return 'text-orange-500';
    if (score === 3) return 'text-yellow-500';
    return 'text-green-500';
});

const isPasswordValid = computed(() => {
    return passwordStrength.value.score === 4;
});

const passwordsMatch = computed(() => {
    if (!form.password_confirmation) return true;
    return form.password === form.password_confirmation;
});

const handleRecaptchaVerified = (token: string) => {
    form.recaptcha_token = token;
    recaptchaVerified.value = true;
};

const handleRecaptchaExpired = () => {
    form.recaptcha_token = '';
    recaptchaVerified.value = false;
};

const handleRecaptchaError = () => {
    form.recaptcha_token = '';
    recaptchaVerified.value = false;
};

<<<<<<< HEAD
const submit = () => {
=======
const submitRegister = () => {
    // Jika OTP field sudah muncul dan ada OTP, berarti ini submit untuk verifikasi OTP
    if (showOtpField.value && form.otp) {
        verifyOtp();
        return;
    }

>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
    // Validate password strength
    if (!isPasswordValid.value) {
        form.setError('password', 'Password harus memenuhi semua kriteria keamanan');
        return;
    }

    // Validate password match
    if (!passwordsMatch.value) {
        form.setError('password_confirmation', 'Konfirmasi password tidak cocok');
        return;
    }

    if (props.recaptchaSiteKey && props.recaptchaSiteKey.trim() !== '') {
        if (!recaptchaVerified.value || !form.recaptcha_token) {
            form.setError('recaptcha_token', 'Harap verifikasi bahwa Anda bukan robot');
            return;
        }
    }

<<<<<<< HEAD
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation', 'recaptcha_token');
=======
    // Submit registrasi awal (email & password)
    form.post(route('register'), {
        onSuccess: (page) => {
            // Cek apakah OTP sudah dikirim dari flash message
            const flashMessage = page.props.flash as any;
            if (flashMessage?.otp_sent) {
                showOtpField.value = true;
                emailSent.value = true;
                form.reset('password', 'password_confirmation', 'recaptcha_token');
            }
            recaptchaVerified.value = false;
            recaptchaRef.value?.reset();
            showPassword.value = false;
            showPasswordConfirmation.value = false;
        },
        onFinish: () => {
            if (!showOtpField.value) {
                form.reset('password', 'password_confirmation', 'recaptcha_token');
            }
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
            recaptchaVerified.value = false;
            recaptchaRef.value?.reset();
            showPassword.value = false;
            showPasswordConfirmation.value = false;
        },
        onError: () => {
            recaptchaRef.value?.reset();
            recaptchaVerified.value = false;
            form.recaptcha_token = '';
        },
    });
};
<<<<<<< HEAD
=======

const verifyOtp = () => {
    form.post(route('email.otp.verify'), {
        onSuccess: () => {
            // OTP verified, akan redirect ke registration steps
        },
        onError: () => {
            form.reset('otp');
        },
    });
};

const resendOtp = () => {
    resendOtpForm.post(route('email.otp.resend'), {
        onSuccess: () => {
            form.reset('otp');
        },
    });
};
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
</script>

<template>
    <AuthBase title="Daftar Akun Baru" description="Buat akun untuk mendaftar sebagai peserta (Atlet, Pelatih, atau Tenaga Pendukung)">
        <Head title="Registrasi" />

<<<<<<< HEAD
        <form @submit.prevent="submit" class="flex flex-col gap-6">
=======
        <form @submit.prevent="submitRegister" class="flex flex-col gap-6">
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

<<<<<<< HEAD
=======
                <!-- OTP Field (muncul setelah email dikirim) -->
                <div v-if="showOtpField" class="grid gap-2">
                    <div class="rounded-lg border bg-card p-4 text-card-foreground shadow-sm">
                        <div class="mb-2 flex items-center gap-2">
                            <Mail class="h-4 w-4 text-primary" />
                            <p class="text-sm font-medium">Kode OTP telah dikirim ke email Anda</p>
                        </div>
                        <p class="mb-3 text-xs text-muted-foreground">
                            Silakan cek inbox atau folder spam email <strong>{{ form.email }}</strong>
                        </p>
                    </div>
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
                    <div class="text-center">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="resendOtpForm.processing"
                            @click="resendOtp"
                            class="text-xs"
                        >
                            <LoaderCircle v-if="resendOtpForm.processing" class="mr-2 h-3 w-3 animate-spin" />
                            Kirim Ulang OTP
                        </Button>
                    </div>
                </div>

>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <div class="group relative">
                        <Lock
                            class="text-muted-foreground group-focus-within:text-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transition-colors"
                        />
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            :tabindex="2"
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Masukkan password"
                            class="border-input bg-background focus:border-ring h-11 rounded-lg pr-10 pl-10 transition-colors"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2 transition-colors"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />

                    <!-- Password Strength Indicator -->
                    <div v-if="form.password" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        :class="[passwordStrengthColor, 'h-full transition-all duration-300']"
                                        :style="{ width: `${(passwordStrength.score / 4) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <span v-if="passwordStrength.label" :class="['text-xs font-medium', passwordStrengthColor]">
                                {{ passwordStrength.label }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-2">
                                <span :class="passwordStrength.checks.length ? 'text-green-500' : 'text-gray-400'">
                                    {{ passwordStrength.checks.length ? '✓' : '○' }}
                                </span>
                                <span :class="passwordStrength.checks.length ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    Minimal 8 karakter
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="passwordStrength.checks.lowercase ? 'text-green-500' : 'text-gray-400'">
                                    {{ passwordStrength.checks.lowercase ? '✓' : '○' }}
                                </span>
                                <span :class="passwordStrength.checks.lowercase ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    Huruf kecil
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="passwordStrength.checks.uppercase ? 'text-green-500' : 'text-gray-400'">
                                    {{ passwordStrength.checks.uppercase ? '✓' : '○' }}
                                </span>
                                <span :class="passwordStrength.checks.uppercase ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    Huruf besar
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="passwordStrength.checks.number ? 'text-green-500' : 'text-gray-400'">
                                    {{ passwordStrength.checks.number ? '✓' : '○' }}
                                </span>
                                <span :class="passwordStrength.checks.number ? 'text-green-600 dark:text-green-400' : 'text-muted-foreground'">
                                    Angka
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Konfirmasi Password</Label>
                    <div class="group relative">
                        <Lock
                            class="text-muted-foreground group-focus-within:text-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transition-colors"
                        />
                        <Input
                            id="password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Konfirmasi password"
                            class="border-input bg-background focus:border-ring h-11 rounded-lg pr-10 pl-10 transition-colors"
                            :class="{ 'border-red-500': form.password_confirmation && !passwordsMatch }"
                        />
                        <button
                            type="button"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-3 -translate-y-1/2 transition-colors"
                        >
                            <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                    <div v-if="form.password_confirmation && !passwordsMatch" class="text-xs text-red-500">Konfirmasi password tidak cocok</div>
                    <div v-else-if="form.password_confirmation && passwordsMatch" class="text-xs text-green-500">✓ Password cocok</div>
                </div>

                <!-- reCAPTCHA v2 (dengan checkbox dan challenge default) -->
                <div v-if="recaptchaSiteKey && recaptchaSiteKey.trim() !== ''" class="flex justify-center">
                    <Recaptcha
                        ref="recaptchaRef"
                        :site-key="recaptchaSiteKey"
                        version="v2"
                        theme="light"
                        @verified="handleRecaptchaVerified"
                        @expired="handleRecaptchaExpired"
                        @error="handleRecaptchaError"
                    />
                </div>
                <InputError :message="form.errors.recaptcha_token" />

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="4"
                    :disabled="
                        form.processing ||
<<<<<<< HEAD
                        !isPasswordValid ||
                        !passwordsMatch ||
                        (recaptchaSiteKey && recaptchaSiteKey.trim() !== '' && !recaptchaVerified)
                    "
                >
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Daftar
=======
                        (!showOtpField && (!isPasswordValid || !passwordsMatch || (recaptchaSiteKey && recaptchaSiteKey.trim() !== '' && !recaptchaVerified))) ||
                        (showOtpField && form.otp.length !== 6)
                    "
                >
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    {{ showOtpField ? 'Verifikasi OTP' : 'Daftar' }}
>>>>>>> 6a0e4138e2c341ff3cc4532f6a55f113fcfaf520
                </Button>
            </div>

            <div class="text-muted-foreground text-center text-sm">
                Sudah punya akun?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="5">Masuk</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
