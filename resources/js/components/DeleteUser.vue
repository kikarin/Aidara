<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Hapus akun" description="Hapus akun Anda beserta seluruh data terkait" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Peringatan</p>
                <p class="text-sm">Harap berhati-hati, tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Hapus akun</Button>
                </DialogTrigger>
                <DialogContent>
                    <form class="space-y-6" @submit="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Apakah Anda yakin ingin menghapus akun?</DialogTitle>
                            <DialogDescription>
                                Setelah akun dihapus, seluruh data dan resource terkait akan dihapus permanen. Masukkan kata sandi untuk
                                mengonfirmasi penghapusan akun.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">Kata Sandi</Label>
                            <Input id="password" type="password" name="password" ref="passwordInput" v-model="form.password" placeholder="Kata sandi" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary" @click="closeModal"> Batal </Button>
                            </DialogClose>

                            <Button variant="destructive" :disabled="form.processing">
                                <button type="submit">Hapus akun</button>
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
