<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Recaptcha from '@/components/Recaptcha.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    status?: string;
    recaptchaSiteKey?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
    recaptcha_token: '',
});

const showPassword = ref(false);
const recaptchaRef = ref<InstanceType<typeof Recaptcha> | null>(null);
const recaptchaVerified = ref(false);

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

const submit = () => {
    // Only require reCAPTCHA if site key is provided
    if (props.recaptchaSiteKey && props.recaptchaSiteKey.trim() !== '') {
        if (!recaptchaVerified.value || !form.recaptcha_token) {
            form.setError('recaptcha_token', 'Harap verifikasi bahwa Anda bukan robot');
            return;
        }
    }

    form.post(route('login'), {
        onFinish: () => {
            form.reset('password', 'recaptcha_token');
            recaptchaVerified.value = false;
            recaptchaRef.value?.reset();
        },
        onError: () => {
            recaptchaRef.value?.reset();
            recaptchaVerified.value = false;
            form.recaptcha_token = '';
        },
    });
};
</script>

<template>
    <AuthBase title="Dispora" description="Dinas Pemuda dan Olahraga">
        <Head title="Login" />

        <div
            v-if="status"
            class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-center text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email -->
            <div class="space-y-2">
                <Label for="email" class="text-sm font-medium">Email</Label>
                <div class="group relative">
                    <Mail
                        class="text-muted-foreground group-focus-within:text-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transition-colors"
                    />
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="Masukkan email Anda"
                        class="border-input bg-background focus:border-ring h-11 rounded-lg pl-10 transition-colors"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <Label for="password" class="text-sm font-medium">Password</Label>
                <div class="group relative">
                    <Lock
                        class="text-muted-foreground group-focus-within:text-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transition-colors"
                    />
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Masukkan password Anda"
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
            </div>

            <!-- Remember me -->
            <div class="flex items-center justify-between">
                <Label for="remember" class="flex cursor-pointer items-center space-x-3">
                    <Checkbox id="remember" v-model="form.remember" :tabindex="3" />
                    <span class="text-muted-foreground text-sm">Ingat saya</span>
                </Label>
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

            <!-- Submit -->
            <Button
                type="submit"
                class="h-10 w-full rounded-lg font-medium"
                :tabindex="4"
                :disabled="form.processing || (recaptchaSiteKey && recaptchaSiteKey.trim() !== '' && !recaptchaVerified)"
            >
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ form.processing ? 'Memproses...' : 'Masuk ke Sistem' }}
            </Button>
        </form>
    </AuthBase>
</template>
