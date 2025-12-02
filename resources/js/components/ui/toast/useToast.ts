import { ref } from 'vue'

export type ToastVariant = 'success' | 'destructive' | 'default'

// GLOBAL SINGLETON STATE
const toasts = ref<{ title: string; variant: ToastVariant }[]>([])

export function useToast() {
  function toast({ title, variant = 'default' }: { title: string; variant?: ToastVariant }) {
    toasts.value.push({ title, variant })

    setTimeout(() => {
      toasts.value.shift()
    }, 5000)
  }

  

  return { toast, toasts }
}
