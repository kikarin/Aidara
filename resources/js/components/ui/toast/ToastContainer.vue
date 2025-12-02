<script setup lang="ts">
import { useToast } from './useToast'
import { TransitionGroup } from 'vue'
import { CheckCircle, XCircle, AlertCircle, Info, X } from 'lucide-vue-next'

const { toasts } = useToast()

const getToastIcon = (variant: string) => {
  switch (variant) {
    case 'success': return CheckCircle
    case 'destructive': return XCircle
    case 'warning': return AlertCircle
    default: return Info
  }
}

const getToastStyles = (variant: string) => {
  const baseStyles = 'backdrop-blur-sm border'
  
  switch (variant) {
    case 'success':
      return `${baseStyles} bg-white dark:bg-gray-800 border-green-200 dark:border-green-800 text-gray-900 dark:text-gray-100`
    case 'destructive':
      return `${baseStyles} bg-white dark:bg-gray-800 border-red-200 dark:border-red-800 text-gray-900 dark:text-gray-100`
    case 'warning':
      return `${baseStyles} bg-white dark:bg-gray-800 border-yellow-200 dark:border-yellow-800 text-gray-900 dark:text-gray-100`
    default:
      return `${baseStyles} bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100`
  }
}

const getIconStyles = (variant: string) => {
  switch (variant) {
    case 'success': return 'text-green-500 dark:text-green-400'
    case 'destructive': return 'text-red-500 dark:text-red-400'
    case 'warning': return 'text-yellow-500 dark:text-yellow-400'
    default: return 'text-gray-500 dark:text-gray-400'
  }
}
</script>

<template>
  <div class="fixed top-4 right-4 z-[9999] w-full max-w-sm pointer-events-none">
    <TransitionGroup name="toast" tag="div" class="space-y-2">
      <div
        v-for="(toast, index) in toasts"
        :key="`toast-${index}-${toast.id || Date.now()}`"
        class="pointer-events-auto relative group"
      >
        <div
          class="flex items-center gap-3 p-4 pr-12 rounded-lg shadow-lg border transition-all duration-200 hover:shadow-xl"
          :class="getToastStyles(toast.variant || 'default')"
        >
          <!-- Progress Bar -->
          <div 
            v-if="toast.duration"
            class="absolute bottom-0 left-0 h-1 bg-current opacity-20 rounded-b-lg transition-all duration-linear"
            :style="{ 
              width: '100%',
              animation: `shrink ${toast.duration}ms linear forwards`
            }"
          />
          
          <!-- Icon -->
          <div class="flex-shrink-0">
            <component
              :is="getToastIcon(toast.variant || 'default')"
              class="w-5 h-5"
              :class="getIconStyles(toast.variant || 'default')"
            />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <h4 
              v-if="toast.title" 
              class="text-sm font-medium leading-5"
              :class="{ 'mb-1': toast.description }"
            >
              {{ toast.title }}
            </h4>
            <p 
              v-if="toast.description" 
              class="text-sm text-gray-600 dark:text-gray-400 leading-5"
            >
              {{ toast.description }}
            </p>
          </div>

          <!-- Close Button -->
          <button
            @click="toasts.splice(index, 1)"
            class="absolute top-3 right-3 p-1 rounded-md transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            aria-label="Close notification"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
@keyframes shrink {
  from { width: 100%; }
  to { width: 0%; }
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

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .dark\:bg-gray-900\/95 {
    background-color: rgb(17 24 39 / 0.95);
  }
  
  .dark\:text-green-100 {
    color: rgb(220 252 231);
  }
  
  .dark\:text-red-100 {
    color: rgb(254 226 226);
  }
  
  .dark\:text-amber-100 {
    color: rgb(254 243 199);
  }
  
  .dark\:text-blue-100 {
    color: rgb(219 234 254);
  }
}
</style>