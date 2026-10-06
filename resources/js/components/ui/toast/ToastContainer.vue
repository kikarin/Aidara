<script setup lang="ts">
import { useToast } from './useToast';
import { TransitionGroup } from 'vue';
import { AlertCircle, CheckCircle, Info, X, XCircle } from 'lucide-vue-next';

const { toasts } = useToast();

const getToastIcon = (variant: string) => {
    switch (variant) {
        case 'success':
            return CheckCircle;
        case 'destructive':
            return XCircle;
        case 'warning':
            return AlertCircle;
        default:
            return Info;
    }
};

const getToastStyles = (variant: string) => {
    const baseStyles = 'backdrop-blur-sm border bg-card text-foreground';

    switch (variant) {
        case 'success':
            return `${baseStyles} border-[color-mix(in_oklch,var(--success-foreground)_25%,transparent)]`;
        case 'destructive':
            return `${baseStyles} border-destructive/30`;
        case 'warning':
            return `${baseStyles} border-[color-mix(in_oklch,var(--stat-tenaga-foreground)_25%,transparent)]`;
        default:
            return `${baseStyles} border-border`;
    }
};

const getIconStyles = (variant: string) => {
    switch (variant) {
        case 'success':
            return 'text-[var(--success-foreground)]';
        case 'destructive':
            return 'text-destructive';
        case 'warning':
            return 'text-[var(--stat-tenaga-foreground)]';
        default:
            return 'text-muted-foreground';
    }
};
</script>

<template>
    <div class="pointer-events-none fixed top-4 right-4 z-[9999] w-full max-w-sm">
        <TransitionGroup name="toast" tag="div" class="space-y-2">
            <div v-for="(toast, index) in toasts" :key="`toast-${index}-${toast.id || Date.now()}`" class="group pointer-events-auto relative">
                <div
                    class="flex items-center gap-3 rounded-lg border p-4 pr-12 shadow-lg transition-all duration-200 hover:shadow-xl"
                    :class="getToastStyles(toast.variant || 'default')"
                >
                    <div
                        v-if="toast.duration"
                        class="absolute bottom-0 left-0 h-1 rounded-b-lg bg-current opacity-20 transition-all duration-linear"
                        :style="{
                            width: '100%',
                            animation: `shrink ${toast.duration}ms linear forwards`,
                        }"
                    />

                    <div class="flex-shrink-0">
                        <component
                            :is="getToastIcon(toast.variant || 'default')"
                            class="h-5 w-5"
                            :class="getIconStyles(toast.variant || 'default')"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <h4 v-if="toast.title" class="text-sm leading-5 font-medium" :class="{ 'mb-1': toast.description }">
                            {{ toast.title }}
                        </h4>
                        <p v-if="toast.description" class="text-muted-foreground text-sm leading-5">
                            {{ toast.description }}
                        </p>
                    </div>

                    <button
                        @click="toasts.splice(index, 1)"
                        class="text-muted-foreground hover:bg-accent hover:text-accent-foreground absolute top-3 right-3 rounded-md p-1 transition-all duration-200"
                        aria-label="Tutup notifikasi"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.toast-enter-active {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
    opacity: 0;
    transform: translateX(100%) scale(0.95);
}

.toast-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.95);
}

.toast-move {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
