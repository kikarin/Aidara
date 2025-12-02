<script setup lang="ts">
import { cn } from '@/lib/utils'
import { computed } from 'vue'

const props = defineProps<{
  variant?: 'default' | 'destructive' | 'warning' | 'success' | 'info'
  class?: string
  dismissible?: boolean
}>()

const emit = defineEmits<{
  dismiss: []
}>()

const alertClasses = computed(() => {
  const baseClasses = 'relative w-full rounded-xl border backdrop-blur-sm transition-all duration-300 ease-in-out shadow-lg hover:shadow-xl'
  
const variantClasses = {
  default: 'border-gray-200/50 bg-white/80 text-gray-900 dark:border-gray-700/50 dark:bg-gray-800/80 dark:text-gray-100',
  
  destructive:
    'border-red-300/40 bg-red-200/90 text-red-800 ' + // ⬅ lebih kuat di light mode
    'dark:border-red-700/30 dark:bg-red-900/30 dark:text-red-200 dark:[&>svg]:text-red-300 [&>svg]:text-red-600', // ⬅ less neon, lebih soft

  warning: 'border-amber-200/50 bg-gradient-to-r from-amber-50/90 to-amber-100/80 text-amber-900 dark:border-amber-800/50 dark:from-amber-950/90 dark:to-amber-900/80 dark:text-amber-100 [&>svg]:text-amber-600 dark:[&>svg]:text-amber-400',
  
  success: 'border-emerald-200/50 bg-gradient-to-r from-emerald-50/90 to-emerald-100/80 text-emerald-900 dark:border-emerald-800/50 dark:from-emerald-950/90 dark:to-emerald-900/80 dark:text-emerald-100 [&>svg]:text-emerald-600 dark:[&>svg]:text-emerald-400',
  
  info: 'border-blue-200/50 bg-gradient-to-r from-blue-50/90 to-blue-100/80 text-blue-900 dark:border-blue-800/50 dark:from-blue-950/90 dark:to-blue-900/80 dark:text-blue-100 [&>svg]:text-blue-600 dark:[&>svg]:text-blue-400'
}

  
  const paddingClasses = props.dismissible 
    ? 'p-6 pr-12' 
    : 'p-6'
  
  return cn(
    baseClasses,
    variantClasses[props.variant || 'default'],
    paddingClasses,
    props.class
  )
})

const handleDismiss = () => {
  emit('dismiss')
}
</script>

<template>
  <div
    :class="alertClasses"
    role="alert"
  >
    <!-- Dismiss Button -->
    <button
      v-if="dismissible"
      @click="handleDismiss"
      class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg transition-colors hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-2"
      aria-label="Dismiss alert"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
    
    <!-- Content -->
    <div class="space-y-2">
      <slot />
    </div>
  </div>
</template>