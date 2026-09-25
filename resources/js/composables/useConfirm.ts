import { ref } from 'vue';

export type ConfirmOptions = {
    title: string;
    description?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
};

type PendingConfirm = {
    open: boolean;
    options: ConfirmOptions;
    resolve: ((value: boolean) => void) | null;
};

const pending = ref<PendingConfirm>({ open: false, options: { title: '' }, resolve: null });

export function useConfirm() {
    const confirm = (options: ConfirmOptions): Promise<boolean> =>
        new Promise((resolve) => {
            pending.value = { open: true, options, resolve };
        });

    const settle = (value: boolean) => {
        pending.value.resolve?.(value);
        pending.value = { open: false, options: { title: '' }, resolve: null };
    };

    return { pending, confirm, settle };
}
