<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

// Type definition for Google reCAPTCHA v3
declare global {
    interface Window {
        grecaptcha: {
            ready: (callback: () => void) => void;
            execute: (
                siteKey: string,
                options: {
                    action: string;
                },
            ) => Promise<string>;
            render: (
                container: string | HTMLElement,
                options: {
                    sitekey: string;
                    theme?: 'light' | 'dark';
                    size?: 'normal' | 'compact';
                    callback?: (token: string) => void;
                    'expired-callback'?: () => void;
                    'error-callback'?: () => void;
                },
            ) => number;
            reset: (widgetId: number) => void;
            getResponse: (widgetId: number) => string | null;
        };
    }
}

const props = defineProps<{
    siteKey: string;
    version?: 'v2' | 'v3';
    theme?: 'light' | 'dark';
    size?: 'normal' | 'compact';
    action?: string;
}>();

const emit = defineEmits<{
    verified: [token: string];
    expired: [];
    error: [];
}>();

const recaptchaId = ref<number | null>(null);
const widgetId = ref<number | null>(null);
const isLoaded = ref(false);
const isV3 = ref(props.version === 'v3');
const isV2 = ref(props.version === 'v2' || !props.version); // Default to v2

const loadRecaptcha = () => {
    // Check if already loaded
    if (window.grecaptcha) {
        isLoaded.value = true;
        if (isV3.value) {
            executeV3();
        } else if (isV2.value) {
            // Wait for DOM to be ready before rendering v2
            setTimeout(() => {
                renderRecaptcha();
            }, 100);
        }
        return;
    }

    // Check if script already exists
    const existingScript = document.querySelector('script[src*="recaptcha"]');
    if (existingScript) {
        // Script exists, wait for it to load
        const checkInterval = setInterval(() => {
            if (window.grecaptcha) {
                clearInterval(checkInterval);
                isLoaded.value = true;
                if (isV3.value) {
                    executeV3();
                } else if (isV2.value) {
                    setTimeout(() => {
                        renderRecaptcha();
                    }, 100);
                }
            }
        }, 100);

        // Timeout after 5 seconds
        setTimeout(() => {
            clearInterval(checkInterval);
        }, 5000);
        return;
    }

    // Create and load script
    const script = document.createElement('script');
    if (isV3.value) {
        // v3 uses site key in URL
        script.src = `https://www.google.com/recaptcha/api.js?render=${props.siteKey}&hl=id`;
    } else {
        // v2 uses explicit render (without site key in URL)
        script.src = `https://www.google.com/recaptcha/api.js?render=explicit&hl=id`;
    }
    script.async = true;
    script.defer = true;
    script.onload = () => {
        // Wait a bit for grecaptcha to be available
        const checkInterval = setInterval(() => {
            if (window.grecaptcha) {
                clearInterval(checkInterval);
                isLoaded.value = true;
                if (isV3.value) {
                    window.grecaptcha.ready(() => {
                        executeV3();
                    });
                } else if (isV2.value) {
                    // Use ready() for v2 as well to ensure it's fully loaded
                    window.grecaptcha.ready(() => {
                        setTimeout(() => {
                            renderRecaptcha();
                        }, 100);
                    });
                }
            }
        }, 100);

        setTimeout(() => {
            clearInterval(checkInterval);
        }, 5000);
    };
    script.onerror = () => {
        console.error('Failed to load reCAPTCHA script');
        emit('error');
    };
    document.head.appendChild(script);
};

const executeV3 = async () => {
    if (!window.grecaptcha || !isLoaded.value) {
        setTimeout(executeV3, 100);
        return;
    }

    try {
        window.grecaptcha.ready(async () => {
            try {
                const token = await window.grecaptcha.execute(props.siteKey, {
                    action: props.action || 'submit',
                });
                emit('verified', token);
            } catch (error) {
                console.error('reCAPTCHA v3 execution error:', error);
                emit('error');
            }
        });
    } catch (error) {
        console.error('reCAPTCHA v3 error:', error);
        emit('error');
    }
};

const renderRecaptcha = () => {
    if (!window.grecaptcha || !isLoaded.value) {
        setTimeout(renderRecaptcha, 100);
        return;
    }

    const container = document.getElementById(`recaptcha-${recaptchaId.value}`);
    if (!container) {
        console.warn('reCAPTCHA container not found, retrying...');
        setTimeout(renderRecaptcha, 100);
        return;
    }

    // Validate site key format
    if (!props.siteKey || props.siteKey.trim() === '') {
        console.error('reCAPTCHA Site Key is empty');
        emit('error');
        return;
    }

    // Check if site key looks like v2 key (v2 keys usually start with 6L)
    // v3 keys also start with 6L, so we can't distinguish by prefix alone
    // But we can validate the key is present

    try {
        widgetId.value = window.grecaptcha.render(container, {
            sitekey: props.siteKey,
            theme: props.theme || 'light',
            size: props.size || 'normal',
            callback: (token: string) => {
                console.log('reCAPTCHA v2 verified successfully');
                emit('verified', token);
            },
            'expired-callback': () => {
                console.warn('reCAPTCHA v2 token expired');
                emit('expired');
            },
            'error-callback': () => {
                console.error('reCAPTCHA v2 error callback triggered');
                console.error('ERROR: Site Key mungkin tidak valid untuk reCAPTCHA v2!');
                console.error('Pastikan:');
                console.error('1. Site Key di .env adalah key v2 (bukan v3)');
                console.error('2. Domain sudah terdaftar di Google reCAPTCHA Console');
                console.error('3. Sudah menjalankan: php artisan config:clear && php artisan config:cache');
                emit('error');
            },
        });
        console.log('reCAPTCHA v2 rendered successfully', {
            widgetId: widgetId.value,
            siteKey: props.siteKey.substring(0, 10) + '...',
        });
    } catch (error: any) {
        console.error('reCAPTCHA v2 render error:', error);
        if (error && error.message && error.message.includes('Invalid site key')) {
            console.error('ERROR: Site Key tidak valid untuk reCAPTCHA v2!');
            console.error('Pastikan:');
            console.error('1. Site Key di .env adalah key v2 (bukan v3)');
            console.error('2. Domain sudah terdaftar di Google reCAPTCHA Console');
            console.error('3. Sudah menjalankan: php artisan config:clear && php artisan config:cache');
        }
        emit('error');
    }
};

const reset = () => {
    if (isV3.value) {
        // For v3, re-execute to get new token
        if (window.grecaptcha && isLoaded.value) {
            executeV3();
        }
    } else {
        // For v2, reset widget
        if (window.grecaptcha && widgetId.value !== null) {
            window.grecaptcha.reset(widgetId.value);
        }
    }
};

const getResponse = (): string | null => {
    if (isV3.value) {
        // v3 doesn't have persistent response, need to execute again
        return null;
    } else {
        if (window.grecaptcha && widgetId.value !== null) {
            return window.grecaptcha.getResponse(widgetId.value);
        }
    }
    return null;
};

onMounted(() => {
    recaptchaId.value = Math.floor(Math.random() * 1000000);

    // Validate site key before proceeding
    if (!props.siteKey || props.siteKey.trim() === '') {
        console.error('reCAPTCHA Site Key tidak ditemukan! Pastikan RECAPTCHA_SITE_KEY sudah di-set di file .env');
        return;
    }

    console.log('reCAPTCHA component mounted', {
        version: props.version || 'v2 (default)',
        siteKey: props.siteKey ? `${props.siteKey.substring(0, 10)}...` : 'not set',
        isV2: isV2.value,
        isV3: isV3.value,
    });

    // Wait for DOM to be ready
    setTimeout(() => {
        loadRecaptcha();
    }, 100);
});

// Watch for action changes in v3
watch(
    () => props.action,
    () => {
        if (isV3.value && isLoaded.value && window.grecaptcha) {
            executeV3();
        }
    },
);

onUnmounted(() => {
    if (!isV3.value && window.grecaptcha && widgetId.value !== null) {
        try {
            window.grecaptcha.reset(widgetId.value);
        } catch {
            // Ignore errors
        }
    }
});

defineExpose({
    reset,
    getResponse,
});
</script>

<template>
    <!-- v3 doesn't need a visible container, it's invisible -->
    <div v-if="!isV3" :id="`recaptcha-${recaptchaId}`" class="recaptcha-container"></div>
    <!-- v3 badge will appear automatically at bottom right -->
</template>

<style scoped>
.recaptcha-container {
    display: flex;
    justify-content: center;
    margin: 1rem 0;
}
</style>
